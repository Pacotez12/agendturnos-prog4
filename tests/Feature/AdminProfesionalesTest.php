<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use App\Notifications\InvitacionProfesional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class AdminProfesionalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_listado_muestra_estado_activa_y_sin_servicios(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $conServicios = Profesional::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'González',
            'matricula' => 'MP-1234',
            'orden' => 1,
        ]);
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);
        $conServicios->servicios()->attach($servicio->id);

        $sinServicios = Profesional::factory()->create([
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'orden' => 2,
        ]);

        $response = $this->actingAs($funcionario)->get(route('panel.profesionales'));

        $response->assertOk();
        $response->assertSee('Ana González');
        $response->assertSee('Activa');
        $response->assertSee('Laura Benítez');
        $response->assertSee('Sin servicios');
        $response->assertSee('Nueva profesional');
        $response->assertSee('Editar');
    }

    public function test_funcionario_puede_dar_de_alta_una_profesional(): void
    {
        Notification::fake();

        $funcionario = User::factory()->funcionario()->create();
        Profesional::factory()->create(['orden' => 2]);

        $response = $this->actingAs($funcionario)->post(route('panel.profesionales.store'), [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'email' => 'laura.benitez@correo.com',
        ]);

        $user = User::where('email', 'laura.benitez@correo.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(RolUsuario::Profesional, $user->rol);

        $profesional = Profesional::where('matricula', 'MP-9012')->first();
        $this->assertNotNull($profesional);
        $this->assertSame($user->id, $profesional->user_id);
        $this->assertSame('Laura', $profesional->nombre);
        $this->assertSame('Benítez', $profesional->apellido);
        $this->assertSame(3, $profesional->orden);

        $response->assertRedirect(route('panel.profesionales.servicios', $profesional));
        $response->assertSessionHas(
            'success',
            'Laura Benítez quedó dada de alta. Le enviamos un correo a laura.benitez@correo.com para que cree su contraseña. Siguiente paso: elegí los servicios que ofrece y cargale el horario.'
        );

        Notification::assertSentTo($user, InvitacionProfesional::class);
    }

    public function test_alta_rechaza_correo_o_matricula_repetidos(): void
    {
        Notification::fake();

        $funcionario = User::factory()->funcionario()->create();
        $existenteUser = User::factory()->profesional()->create(['email' => 'ana@glowdental.test']);
        Profesional::factory()->create([
            'user_id' => $existenteUser->id,
            'matricula' => 'MP-1234',
        ]);

        $resCorreo = $this->actingAs($funcionario)->post(route('panel.profesionales.store'), [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9999',
            'email' => 'ana@glowdental.test',
        ]);

        $resCorreo->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('profesionales', ['matricula' => 'MP-9999']);

        $resMatricula = $this->actingAs($funcionario)->post(route('panel.profesionales.store'), [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-1234',
            'email' => 'laura@glowdental.test',
        ]);

        $resMatricula->assertSessionHasErrors('matricula');
        $this->assertDatabaseMissing('users', ['email' => 'laura@glowdental.test']);

        Notification::assertNothingSent();
    }

    public function test_profesional_y_paciente_reciben_403_en_alta_y_edicion(): void
    {
        $paciente = User::factory()->paciente()->create();
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $datos = [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'email' => 'laura@correo.com',
        ];

        $this->actingAs($paciente)->post(route('panel.profesionales.store'), $datos)->assertForbidden();
        $this->actingAs($profesionalUser)->post(route('panel.profesionales.store'), $datos)->assertForbidden();

        $this->actingAs($paciente)->put(route('panel.profesionales.update', $profesional), $datos)->assertForbidden();
        $this->actingAs($profesionalUser)->put(route('panel.profesionales.update', $profesional), $datos)->assertForbidden();
    }

    public function test_funcionario_puede_editar_profesional_sin_enviar_correo(): void
    {
        Notification::fake();

        $funcionario = User::factory()->funcionario()->create();
        $userProf = User::factory()->profesional()->create(['email' => 'laura.viejo@correo.com']);
        $profesional = Profesional::factory()->create([
            'user_id' => $userProf->id,
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
        ]);

        $otraUser = User::factory()->profesional()->create(['email' => 'otra@correo.com']);
        $otraProf = Profesional::factory()->create([
            'user_id' => $otraUser->id,
            'matricula' => 'MP-5555',
        ]);

        // Editar conservando su propia matrícula y cambiando correo y nombre
        $response = $this->actingAs($funcionario)->put(route('panel.profesionales.update', $profesional), [
            'nombre' => 'Laura María',
            'apellido' => 'Benítez Rojas',
            'matricula' => 'MP-9012',
            'email' => 'laura.nuevo@correo.com',
        ]);

        $response->assertRedirect(route('panel.profesionales'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('profesionales', [
            'id' => $profesional->id,
            'nombre' => 'Laura María',
            'apellido' => 'Benítez Rojas',
            'matricula' => 'MP-9012',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $userProf->id,
            'email' => 'laura.nuevo@correo.com',
        ]);

        // Intentar usar correo o matrícula de otra profesional falla
        $resConflicto = $this->actingAs($funcionario)->put(route('panel.profesionales.update', $profesional), [
            'nombre' => 'Laura María',
            'apellido' => 'Benítez Rojas',
            'matricula' => $otraProf->matricula,
            'email' => $otraUser->email,
        ]);

        $resConflicto->assertSessionHasErrors(['matricula', 'email']);

        Notification::assertNothingSent();
    }

    public function test_enlace_de_invitacion_usa_app_url_y_no_el_header_host(): void
    {
        Notification::fake();

        $funcionario = User::factory()->funcionario()->create();

        $this->actingAs($funcionario)->post('http://atacante.example/panel/profesionales', [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'email' => 'laura.benitez@correo.com',
        ]);

        $user = User::where('email', 'laura.benitez@correo.com')->firstOrFail();

        Notification::assertSentTo($user, InvitacionProfesional::class, function (InvitacionProfesional $notification) use ($user) {
            $mail = $notification->toMail($user);
            $expectedBase = rtrim(config('app.url'), '/');

            return str_starts_with($mail->actionUrl, $expectedBase)
                && ! str_contains($mail->actionUrl, 'atacante.example');
        });
    }

    public function test_invitacion_no_vence_a_los_60_minutos_y_vence_a_los_7_dias(): void
    {
        Notification::fake();

        $funcionario = User::factory()->funcionario()->create();

        $this->actingAs($funcionario)->post(route('panel.profesionales.store'), [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'email' => 'laura.benitez@correo.com',
        ]);

        $user = User::where('email', 'laura.benitez@correo.com')->firstOrFail();

        $token = null;
        Notification::assertSentTo($user, InvitacionProfesional::class, function (InvitacionProfesional $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        auth()->logout();

        // A los 7 días y 1 minuto ya venció
        $this->travel(7)->days();
        $this->travel(1)->minutes();

        $resVencido = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'laura.benitez@correo.com',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $resVencido->assertSessionHasErrors([
            'email' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
        ]);

        // Si estamos dentro de los 7 días (por ejemplo 6 días después del alta, superando los 60 min normales), funciona
        $this->travelBack();
        $this->travel(6)->days();

        $resValido = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'laura.benitez@correo.com',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $resValido->assertRedirect(route('login'));
        $resValido->assertSessionHas('success', 'Tu contraseña fue actualizada.');
    }

    public function test_flujo_completo_alta_crear_contrasena_y_login_como_profesional(): void
    {
        Notification::fake();

        $funcionario = User::factory()->funcionario()->create();

        $this->actingAs($funcionario)->post(route('panel.profesionales.store'), [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'email' => 'laura.benitez@correo.com',
        ]);

        $user = User::where('email', 'laura.benitez@correo.com')->firstOrFail();

        $token = null;
        Notification::assertSentTo($user, InvitacionProfesional::class, function (InvitacionProfesional $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        auth()->logout();

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => 'laura.benitez@correo.com',
        ]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'laura.benitez@correo.com',
            'password' => 'clave-profesional-123',
            'password_confirmation' => 'clave-profesional-123',
        ])->assertRedirect(route('login'));

        $loginResponse = $this->post(route('login'), [
            'email' => 'laura.benitez@correo.com',
            'password' => 'clave-profesional-123',
        ]);

        $loginResponse->assertRedirect(route('panel.inicio'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_token_de_recuperacion_de_hace_2_horas_es_rechazado_y_invitacion_de_hace_2_dias_funciona(): void
    {
        $paciente = User::factory()->paciente()->create([
            'email' => 'paciente@correo.com',
            'password' => Hash::make('clave-vieja-123'),
        ]);

        $tokenRecuperacion = Password::broker('users')->createToken($paciente);

        // 2 horas después, el token común de recuperación (60 min) debe estar vencido
        $this->travel(2)->hours();

        $resRecuperacion = $this->post(route('password.update'), [
            'token' => $tokenRecuperacion,
            'email' => 'paciente@correo.com',
            'password' => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ]);

        $resRecuperacion->assertSessionHasErrors([
            'email' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
        ]);
        $this->assertTrue(Hash::check('clave-vieja-123', $paciente->fresh()->password));

        $this->travelBack();

        // En cambio, una invitación de profesional creada hace 2 días sigue funcionando
        $profesionalUser = User::factory()->profesional()->create([
            'email' => 'profesional@correo.com',
            'password' => Hash::make('clave-random-inicial'),
        ]);
        Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $tokenInvitacion = Password::broker('invitaciones')->createToken($profesionalUser);

        $this->travel(2)->days();

        $resInvitacion = $this->post(route('password.update'), [
            'token' => $tokenInvitacion,
            'email' => 'profesional@correo.com',
            'password' => 'clave-profesional-nueva',
            'password_confirmation' => 'clave-profesional-nueva',
        ]);

        $resInvitacion->assertRedirect(route('login'));
        $resInvitacion->assertSessionHas('success', 'Tu contraseña fue actualizada.');
        $this->assertTrue(Hash::check('clave-profesional-nueva', $profesionalUser->fresh()->password));
    }

    public function test_si_falla_envio_de_invitacion_el_alta_no_da_500_loguea_y_avisa_en_mensaje(): void
    {
        Log::spy();

        $this->app->bind(InvitacionProfesional::class, fn (object $app, array $params) => new class($params['token'] ?? '') extends InvitacionProfesional
        {
            public function toMail(object $notifiable): MailMessage
            {
                throw new RuntimeException('Servidor SMTP no disponible');
            }
        });

        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->post(route('panel.profesionales.store'), [
            'nombre' => 'Laura',
            'apellido' => 'Benítez',
            'matricula' => 'MP-9012',
            'email' => 'laura.benitez@correo.com',
        ]);

        $profesional = Profesional::where('matricula', 'MP-9012')->first();
        $this->assertNotNull($profesional);

        $response->assertRedirect(route('panel.profesionales.servicios', $profesional));
        $mensaje = session('success');
        $this->assertIsString($mensaje);
        $this->assertStringContainsString('Laura Benítez quedó dada de alta', $mensaje);
        $this->assertStringContainsString('no se pudo enviar', $mensaje);

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_recuperacion_usada_invalida_invitacion_pendiente(): void
    {
        $profesionalUser = User::factory()->profesional()->create([
            'email' => 'laura.benitez@correo.com',
            'password' => Hash::make('clave-inicial-random'),
        ]);
        Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $tokenInvitacion = Password::broker('invitaciones')->createToken($profesionalUser);
        $tokenRecuperacion = Password::broker('users')->createToken($profesionalUser);

        // Usa con éxito el link de recuperación
        $this->post(route('password.update'), [
            'token' => $tokenRecuperacion,
            'email' => 'laura.benitez@correo.com',
            'password' => 'clave-por-recuperacion',
            'password_confirmation' => 'clave-por-recuperacion',
        ])->assertRedirect(route('login'));

        // La invitación vieja ya no debe servir
        $resInvitacion = $this->post(route('password.update'), [
            'token' => $tokenInvitacion,
            'email' => 'laura.benitez@correo.com',
            'password' => 'clave-por-invitacion',
            'password_confirmation' => 'clave-por-invitacion',
        ]);

        $resInvitacion->assertSessionHasErrors([
            'email' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
        ]);
        $this->assertTrue(Hash::check('clave-por-recuperacion', $profesionalUser->fresh()->password));
    }

    public function test_invitacion_usada_invalida_recuperacion_pendiente(): void
    {
        $profesionalUser = User::factory()->profesional()->create([
            'email' => 'laura.benitez@correo.com',
            'password' => Hash::make('clave-inicial-random'),
        ]);
        Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $tokenInvitacion = Password::broker('invitaciones')->createToken($profesionalUser);
        $tokenRecuperacion = Password::broker('users')->createToken($profesionalUser);

        // Usa con éxito el link de invitación
        $this->post(route('password.update'), [
            'token' => $tokenInvitacion,
            'email' => 'laura.benitez@correo.com',
            'password' => 'clave-por-invitacion',
            'password_confirmation' => 'clave-por-invitacion',
        ])->assertRedirect(route('login'));

        // La recuperación vieja ya no debe servir
        $resRecuperacion = $this->post(route('password.update'), [
            'token' => $tokenRecuperacion,
            'email' => 'laura.benitez@correo.com',
            'password' => 'clave-por-recuperacion',
            'password_confirmation' => 'clave-por-recuperacion',
        ]);

        $resRecuperacion->assertSessionHasErrors([
            'email' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
        ]);
        $this->assertTrue(Hash::check('clave-por-invitacion', $profesionalUser->fresh()->password));
    }

    public function test_sexto_post_seguido_a_restablecer_contrasena_devuelve_429(): void
    {
        $datos = [
            'token' => 'token-invalido',
            'email' => '  LAURA.BENITEZ@correo.com ',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.update'), $datos)->assertStatus(302);
        }

        $this->post(route('password.update'), $datos)->assertStatus(429);
    }
}
