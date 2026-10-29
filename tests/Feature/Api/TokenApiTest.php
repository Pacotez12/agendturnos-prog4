<?php

namespace Tests\Feature\Api;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class TokenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_crear_token_con_credenciales_validas(): void
    {
        $user = User::factory()->paciente()->create([
            'email' => 'paciente@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/token', [
            'email' => 'paciente@test.com',
            'password' => 'password123',
            'nombre_dispositivo' => 'iPhone de Paciente',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'token_type',
            'usuario' => [
                'id',
                'rol',
            ],
        ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertSame($user->id, $response->json('usuario.id'));
        $this->assertSame('paciente', $response->json('usuario.rol'));
    }

    public function test_crear_token_con_credenciales_invalidas_devuelve_422_con_mensaje_generico(): void
    {
        User::factory()->create([
            'email' => 'usuario@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/token', [
            'email' => 'usuario@test.com',
            'password' => 'contrasena-erronea',
            'nombre_dispositivo' => 'App Móvil',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_crear_token_valida_campos_requeridos(): void
    {
        $response = $this->postJson('/api/v1/token', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password', 'nombre_dispositivo']);
    }

    public function test_revocar_token_autenticado_devuelve_204(): void
    {
        $user = User::factory()->paciente()->create();
        $token = $user->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/token');

        $response->assertStatus(204);
        $this->assertDatabaseEmpty('personal_access_tokens');
    }

    public function test_token_revocado_no_puede_volver_a_usarse(): void
    {
        $user = User::factory()->paciente()->create();
        $token = $user->createToken('TestDevice')->plainTextToken;

        // Revocamos
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/token')
            ->assertStatus(204);

        $this->app['auth']->forgetGuards();

        // Intentamos usarlo de nuevo en un endpoint protegido
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertStatus(401);
    }

    public function test_token_invalido_devuelve_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer token_invalido_123')
            ->getJson('/api/v1/mis-turnos');

        $response->assertStatus(401);
    }

    public function test_throttle_limita_a_5_intentos_por_minuto(): void
    {
        $email = 'throttle@test.com';

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/token', [
                'email' => $email,
                'password' => 'clave-erronea',
                'nombre_dispositivo' => 'Prueba Throttle',
            ]);
            $response->assertStatus(422);
        }

        // El 6to intento debe fallar con 429
        $response = $this->postJson('/api/v1/token', [
            'email' => $email,
            'password' => 'clave-erronea',
            'nombre_dispositivo' => 'Prueba Throttle',
        ]);

        $response->assertStatus(429);
    }

    public function test_token_creado_hace_31_dias_recibe_401(): void
    {
        $user = User::factory()->paciente()->create();
        Paciente::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('TestDevice')->plainTextToken;

        // Simulamos que el token fue creado hace 31 días
        $user->tokens()->update(['created_at' => now()->subDays(31)]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertStatus(401);
    }

    public function test_token_expira_segun_configuracion_personalizada(): void
    {
        config(['sanctum.expiration' => 1]);

        $user = User::factory()->paciente()->create();
        Paciente::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('TestDevice')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos')
            ->assertOk();

        $user->tokens()->update(['created_at' => now()->subMinutes(2)]);

        $this->app['auth']->forgetGuards();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertStatus(401);
    }

    public function test_despues_de_restablecer_contrasena_el_token_anterior_recibe_401(): void
    {
        $user = User::factory()->paciente()->create([
            'email' => 'paciente@test.com',
            'password' => Hash::make('clave-anterior-123'),
        ]);
        Paciente::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('AppMovil')->plainTextToken;

        // El token funciona inicialmente
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos')
            ->assertOk();

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        // Restablecemos la contraseña vía web
        $resetToken = Password::broker()->createToken($user);
        $this->post(route('password.update'), [
            'token' => $resetToken,
            'email' => 'paciente@test.com',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertRedirect(route('login'));

        $this->app['auth']->forgetGuards();

        // El token previo debe ser rechazado con 401
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertStatus(401);
    }

    public function test_crear_token_y_restablecer_contrasena_bloquean_fila_de_usuario_y_reverifican_hash(): void
    {
        $user = User::factory()->paciente()->create([
            'email' => 'paciente@test.com',
            'password' => Hash::make('clave-anterior-123'),
        ]);

        // Simulamos que entre la lectura inicial por email y el lockForUpdate de la transacción
        // otro proceso de restablecimiento de contraseña actualiza el hash en la base de datos.
        $actualizadoEnCarrera = false;
        User::retrieved(function (User $retrieved) use ($user, &$actualizadoEnCarrera): void {
            if (! $actualizadoEnCarrera && $retrieved->id === $user->id) {
                $actualizadoEnCarrera = true;
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['password' => Hash::make('nueva-clave-segura')]);
            }
        });

        DB::enableQueryLog();

        $response = $this->postJson('/api/v1/token', [
            'email' => 'paciente@test.com',
            'password' => 'clave-anterior-123',
            'nombre_dispositivo' => 'AppMovil',
        ]);

        $queriesToken = array_column(DB::getQueryLog(), 'query');
        DB::flushQueryLog();

        $response->assertStatus(422);
        $this->assertDatabaseEmpty('personal_access_tokens');
        $this->assertTrue(
            collect($queriesToken)->contains(fn (string $sql) => str_contains(strtolower($sql), 'from "users"') && str_contains(strtolower($sql), 'for update')),
            'crearToken debe tomar lockForUpdate sobre users.'
        );

        // Ahora verificamos que el restablecimiento de contraseña también toma lockForUpdate sobre users.
        $resetToken = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $resetToken,
            'email' => 'paciente@test.com',
            'password' => 'otra-clave-segura-456',
            'password_confirmation' => 'otra-clave-segura-456',
        ])->assertRedirect(route('login'));

        $queriesReset = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $this->assertTrue(
            collect($queriesReset)->contains(fn (string $sql) => str_contains(strtolower($sql), 'from "users"') && str_contains(strtolower($sql), 'for update')),
            'NewPasswordController::store debe tomar lockForUpdate sobre users.'
        );
    }

    public function test_crear_token_con_correo_inexistente_ejecuta_verificacion_de_hash(): void
    {
        Hash::partialMock()
            ->shouldReceive('check')
            ->once()
            ->withArgs(fn (string $plain, string $hashed) => $plain === 'clave-cualquiera' && str_starts_with($hashed, '$2y$'))
            ->andReturn(false);

        $response = $this->postJson('/api/v1/token', [
            'email' => 'inexistente@test.com',
            'password' => 'clave-cualquiera',
            'nombre_dispositivo' => 'AppMovil',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
