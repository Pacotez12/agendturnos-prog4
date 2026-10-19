<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RestablecerContrasena;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RestablecerContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pantalla_de_pedido_de_enlace_responde_ok(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Recuperá tu contraseña');
        $response->assertSee('Enviar enlace');
        $response->assertSee('Volver a iniciar sesión');
    }

    public function test_pedido_con_email_existente_envia_notificacion_y_muestra_mensaje(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'maria@ejemplo.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'maria@ejemplo.com',
        ]);

        $response->assertSessionHas('status', 'Si el correo está registrado, te enviamos el enlace. Revisá tu bandeja.');

        Notification::assertSentTo($user, RestablecerContrasena::class);
    }

    public function test_pedido_con_email_inexistente_no_envia_notificacion_y_muestra_mismo_mensaje(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'inexistente@ejemplo.com',
        ]);

        $response->assertSessionHas('status', 'Si el correo está registrado, te enviamos el enlace. Revisá tu bandeja.');

        Notification::assertNothingSent();
    }

    public function test_pantalla_de_reset_responde_ok_con_token(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'token-de-prueba',
            'email' => 'maria@ejemplo.com',
        ]));

        $response->assertOk();
        $response->assertSee('Nueva contraseña');
        $response->assertSee('Guardar contraseña');
    }

    public function test_reset_con_token_valido_cambia_contrasena_y_redirige_a_login(): void
    {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->create([
            'email' => 'maria@ejemplo.com',
            'password' => Hash::make('clave-anterior-123'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'maria@ejemplo.com',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Tu contraseña fue actualizada.');

        $this->assertTrue(Hash::check('nueva-clave-segura', $user->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_reset_con_token_invalido_o_vencido_falla_con_mensaje(): void
    {
        $user = User::factory()->create([
            'email' => 'maria@ejemplo.com',
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'token-invalido-o-expirado',
            'email' => 'maria@ejemplo.com',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
        ]);
    }

    public function test_reset_con_contrasena_corta_o_sin_confirmar_falla_validacion(): void
    {
        $user = User::factory()->create([
            'email' => 'maria@ejemplo.com',
        ]);
        $token = Password::broker()->createToken($user);

        // Menos de 8 caracteres
        $resCorta = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'maria@ejemplo.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $resCorta->assertSessionHasErrors('password');

        // Sin confirmar
        $resSinConfirmar = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'maria@ejemplo.com',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'otra-clave-diferente',
        ]);
        $resSinConfirmar->assertSessionHasErrors('password');
    }

    public function test_pantalla_de_login_muestra_link_olvidaste_tu_contrasena(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('¿Olvidaste tu contraseña?');
        $response->assertSee(route('password.request'));
    }

    public function test_correo_de_recuperacion_renderiza_con_marca_y_traducciones(): void
    {
        $user = User::factory()->make(['email' => 'maria@ejemplo.com']);
        $notification = new RestablecerContrasena('token-de-prueba-123');

        $html = (string) $notification->toMail($user)->render();

        $this->assertStringContainsString('Saludos', $html);
        $this->assertStringContainsString('Crear nueva contraseña', $html);
        $this->assertStringContainsString('images/glow-logo.png', $html);
        $this->assertStringNotContainsString('Regards', $html);
    }
}
