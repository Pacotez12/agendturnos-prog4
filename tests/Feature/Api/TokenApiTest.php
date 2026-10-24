<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TokenApiTest extends TestCase
{
    use RefreshDatabase;

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
}
