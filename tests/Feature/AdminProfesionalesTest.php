<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use App\Notifications\InvitacionProfesional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminProfesionalesTest extends TestCase
{
    use RefreshDatabase;

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
}
