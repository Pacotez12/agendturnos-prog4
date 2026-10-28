<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LimitesYAbusoTest extends TestCase
{
    use RefreshDatabase;

    private User $pacienteUser;

    private Paciente $paciente;

    private Profesional $profesional;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        RateLimiter::clear('registro');
        RateLimiter::clear('recuperar-contrasena');
        RateLimiter::clear('solicitar-turno');
        RateLimiter::clear('familia');

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'paciente@test.com']);
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'María',
            'apellido' => 'Rojas',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);
        $this->profesional = Profesional::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($this->servicio->id);

        // Lunes (1) de 08:00 a 12:00
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tercera_solicitud_rechazada_con_mensaje_de_limite(): void
    {
        // 2 solicitudes futuras ya existentes
        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        // Intentar una tercera solicitud
        $response = $this->actingAs($this->pacienteUser)->from(route('paciente.reservar'))->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertRedirect(route('paciente.reservar'));
        $response->assertSessionHasErrors([
            'solicitud' => 'Ya tenés 2 solicitudes esperando confirmación. Cuando la clínica las confirme vas a poder pedir otra.',
        ]);

        // Ver que la vista de reservar renderiza el mensaje y el enlace a Mis turnos cuando hay error de solicitud
        $viewResponse = $this->actingAs($this->pacienteUser)
            ->followingRedirects()
            ->from(route('paciente.reservar'))
            ->post('/turnos', [
                'profesional' => $this->profesional->id,
                'servicio' => $this->servicio->id,
                'fecha' => '2026-10-19',
                'hora' => '09:00',
                'paciente_id' => $this->paciente->id,
            ]);

        $viewResponse->assertOk();
        $viewResponse->assertSee('Ya tenés 2 solicitudes esperando confirmación. Cuando la clínica las confirme vas a poder pedir otra.');
        $viewResponse->assertSee(route('paciente.turnos'));
        $viewResponse->assertSee(route('paciente.turnos'));
    }

    public function test_turnos_confirmados_no_cuentan_para_el_limite(): void
    {
        // 2 turnos confirmados futuros
        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Confirmado,
        ]);

        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('turnos', [
            'paciente_id' => $this->paciente->id,
            'inicio' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado->value,
        ]);
    }

    public function test_solicitudes_de_un_familiar_cuentan_para_la_cuenta(): void
    {
        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Lucas',
            'apellido' => 'Rojas',
        ]);

        // 1 solicitud para María (titular) y 1 solicitud para Lucas (familiar)
        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        Turno::factory()->create([
            'paciente_id' => $familiar->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        // Las solicitudes del familiar cuentan para la cuenta: la propia queda bloqueada
        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->pacienteUser->paciente->id,
        ]);

        $response->assertSessionHasErrors('solicitud');
    }

    public function test_panel_no_tiene_limite_de_agendamiento(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        // El paciente ya tiene 2 solicitudes pendientes futuras
        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 08:30:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        // Funcionario agenda desde el panel
        $response = $this->actingAs($funcionario)->post('/panel/turnos', [
            'paciente_id' => $this->paciente->id,
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('turnos', [
            'paciente_id' => $this->paciente->id,
            'inicio' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'estado' => EstadoTurno::Confirmado->value,
        ]);
    }

    public function test_throttle_login_bloquea_al_sexto_intento_con_mensaje_de_segundos(): void
    {
        User::factory()->paciente()->create([
            'email' => 'login-test@example.com',
            'password' => 'correcto123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'login-test@example.com',
                'password' => 'incorrecto',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'login-test@example.com',
            'password' => 'incorrecto',
        ]);

        $response->assertSessionHasErrors('email');
        $error = session('errors')->first('email');
        $this->assertMatchesRegularExpression('/Demasiados intentos\. Probá de nuevo en \d+ segundos\./', $error);
    }

    public function test_throttle_registro_bloquea_al_cuarto_intento(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/registro', [
                'nombre' => 'Test',
                'apellido' => 'Test',
                'documento_tipo' => 'ci',
                'documento_numero' => '123456'.$i,
                'email' => "test{$i}@example.com",
                'telefono' => '0981123456',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertStatus(302);
        }

        // 4to intento desde la misma IP
        $this->post('/registro', [
            'nombre' => 'Test',
            'apellido' => 'Test',
            'documento_tipo' => 'ci',
            'documento_numero' => '1234569',
            'email' => 'test9@example.com',
            'telefono' => '0981123456',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(429);
    }

    public function test_throttle_recuperar_contrasena_bloquea_al_cuarto_intento(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/olvide-mi-contrasena', [
                'email' => "usuario{$i}@example.com",
            ])->assertStatus(302);
        }

        // 4to intento
        $this->post('/olvide-mi-contrasena', [
            'email' => 'usuario4@example.com',
        ])->assertStatus(429);
    }

    public function test_throttle_solicitar_turno_bloquea_al_onceavo_intento(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->pacienteUser)->post('/turnos', [
                'profesional' => $this->profesional->id,
                'servicio' => $this->servicio->id,
                'fecha' => '2026-10-19',
                'hora' => '09:00',
                'paciente_id' => $this->paciente->id,
            ]);
        }

        // 11vo intento del mismo usuario
        $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ])->assertStatus(429);
    }

    public function test_throttle_mi_familia_bloquea_al_onceavo_intento(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->pacienteUser)->post('/mi-familia', [
                'nombre' => 'Hijo'.$i,
                'apellido' => 'Rojas',
                'tipo_documento' => 'CI',
                'documento' => '800000'.$i,
                'fecha_nacimiento' => '2020-01-01',
            ]);
        }

        // 11vo intento del mismo usuario
        $this->actingAs($this->pacienteUser)->post('/mi-familia', [
            'nombre' => 'Hijo11',
            'apellido' => 'Rojas',
            'tipo_documento' => 'CI',
            'documento' => '8000011',
            'fecha_nacimiento' => '2020-01-01',
        ])->assertStatus(429);
    }

    public function test_throttle_mi_familia_put_bloquea_al_onceavo_intento(): void
    {
        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'tipo_documento' => 'CI',
            'documento' => '8999999',
            'nombre' => 'Lucas',
            'apellido' => 'Rojas',
            'fecha_nacimiento' => '2020-01-01',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->pacienteUser)->put("/mi-familia/{$familiar->id}", [
                'nombre' => 'Lucas'.$i,
                'apellido' => 'Rojas',
                'tipo_documento' => 'CI',
                'documento' => '8999999',
                'fecha_nacimiento' => '2020-01-01',
            ]);
        }

        // 11vo intento
        $this->actingAs($this->pacienteUser)->put("/mi-familia/{$familiar->id}", [
            'nombre' => 'Lucas11',
            'apellido' => 'Rojas',
            'tipo_documento' => 'CI',
            'documento' => '8999999',
            'fecha_nacimiento' => '2020-01-01',
        ])->assertStatus(429);
    }
}
