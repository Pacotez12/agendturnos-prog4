<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_pantalla_login_muestra_inicia_sesion(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Iniciá sesión');
        $response->assertSee('Entrá para reservar y ver tus turnos');
    }

    public function test_usuario_autenticado_es_redirigido_si_visita_login(): void
    {
        $user = User::factory()->paciente()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(route('paciente.inicio'));
    }

    public function test_funcionario_autenticado_es_redirigido_si_visita_login(): void
    {
        $user = User::factory()->funcionario()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect(route('panel.inicio'));
    }

    public function test_paciente_se_loguea_y_va_a_paciente_inicio(): void
    {
        $user = User::factory()->paciente()->create([
            'email' => 'paciente@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'email' => 'paciente@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('paciente.inicio'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_profesional_se_loguea_y_va_a_panel_inicio(): void
    {
        $user = User::factory()->profesional()->create([
            'email' => 'profesional@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'email' => 'profesional@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('panel.inicio'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_funcionario_se_loguea_y_va_a_panel_inicio(): void
    {
        $user = User::factory()->funcionario()->create([
            'email' => 'funcionario@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'email' => 'funcionario@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('panel.inicio'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_credenciales_incorrectas_generan_error_y_no_autentican(): void
    {
        User::factory()->paciente()->create([
            'email' => 'usuario@example.com',
            'password' => 'correcto123',
        ]);

        $response = $this->post('/login', [
            'email' => 'usuario@example.com',
            'password' => 'incorrecto123',
        ]);

        $response->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
        $this->assertGuest();
    }

    public function test_el_error_de_credenciales_se_muestra_una_sola_vez(): void
    {
        $html = $this->from('/login')
            ->followingRedirects()
            ->post('/login', [
                'email' => 'nadie@example.com',
                'password' => 'incorrecto123',
            ])
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'El correo o la contraseña no son correctos.'));
        $this->assertStringContainsString('aria-describedby="email-error"', $html);
    }

    public function test_bloqueo_por_rate_limit_al_sexto_intento_fallido(): void
    {
        User::factory()->paciente()->create([
            'email' => 'usuario@example.com',
            'password' => 'correcto123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'usuario@example.com',
                'password' => 'incorrecto',
            ]);
        }

        // 6to intento fallido
        $response = $this->post('/login', [
            'email' => 'usuario@example.com',
            'password' => 'incorrecto',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Demasiados intentos',
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_logout_cierra_sesion_y_redirige_a_login(): void
    {
        $user = User::factory()->paciente()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_env_example_contiene_session_driver_redis(): void
    {
        $content = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('SESSION_DRIVER=redis', $content);
    }

    public function test_login_con_correo_inexistente_ejecuta_verificacion_de_hash(): void
    {
        Hash::partialMock()
            ->shouldReceive('check')
            ->once()
            ->withArgs(fn (string $plain, string $hashed) => $plain === 'incorrecto123' && str_starts_with($hashed, '$2y$'))
            ->andReturn(false);

        $response = $this->post('/login', [
            'email' => 'inexistente@example.com',
            'password' => 'incorrecto123',
        ]);

        $response->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
        $this->assertGuest();
    }
}
