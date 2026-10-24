<?php

namespace Tests\Feature;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use App\Jobs\EnviarNotificacion;
use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PanelInicioTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $profesionalUser1;

    private Profesional $profesional1;

    private User $profesionalUser2;

    private Profesional $profesional2;

    private User $pacienteUser;

    private Paciente $paciente;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-23 10:00:00', config('app.timezone'))); // Viernes 10:00

        $this->funcionario = User::factory()->funcionario()->create(['email' => 'admin@glowdental.test']);

        $this->profesionalUser1 = User::factory()->profesional()->create(['email' => 'dra.ana@glowdental.test']);
        $this->profesional1 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser1->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->profesionalUser2 = User::factory()->profesional()->create(['email' => 'dra.beatriz@glowdental.test']);
        $this->profesional2 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser2->id,
            'nombre' => 'Beatriz',
            'apellido' => 'Benítez',
        ]);

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'carlos@paciente.test']);
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
            'telefono' => '0981123456',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Limpieza dental',
            'duracion_min' => 30,
            'activo' => true,
        ]);

        $this->profesional1->servicios()->attach($this->servicio->id);
        $this->profesional2->servicios()->attach($this->servicio->id);
    }

    private function crearTurno(string $inicio, string $fin, EstadoTurno $estado = EstadoTurno::Confirmado, ?Profesional $prof = null, ?Paciente $pac = null): Turno
    {
        return Turno::create([
            'paciente_id' => $pac ? $pac->id : $this->paciente->id,
            'profesional_id' => $prof ? $prof->id : $this->profesional1->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse($inicio, config('app.timezone')),
            'fin' => Carbon::parse($fin, config('app.timezone')),
            'estado' => $estado,
        ]);
    }

    public function test_indicadores_con_conteos_correctos_y_filtro_por_rol(): void
    {
        // Viernes 23 de octubre
        // Hoy profesional 1:
        // 1 atendido a las 08:00
        $this->crearTurno('2026-10-23 08:00:00', '2026-10-23 08:30:00', EstadoTurno::Atendido, $this->profesional1);
        // 1 confirmado a las 11:00
        $this->crearTurno('2026-10-23 11:00:00', '2026-10-23 11:30:00', EstadoTurno::Confirmado, $this->profesional1);
        // 1 cancelado a las 14:00 (no cuenta para turnos hoy)
        $this->crearTurno('2026-10-23 14:00:00', '2026-10-23 14:30:00', EstadoTurno::Cancelado, $this->profesional1);
        // 1 solicitado futuro
        $this->crearTurno('2026-10-23 15:00:00', '2026-10-23 15:30:00', EstadoTurno::Solicitado, $this->profesional1);

        // Turnos de la semana (Lunes 19 a Jueves 22) para prof 1:
        // 2 atendidos lunes
        $this->crearTurno('2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Atendido, $this->profesional1);
        $this->crearTurno('2026-10-19 10:00:00', '2026-10-19 10:30:00', EstadoTurno::Atendido, $this->profesional1);
        // 1 ausente martes
        $this->crearTurno('2026-10-20 09:00:00', '2026-10-20 09:30:00', EstadoTurno::Ausente, $this->profesional1);

        // Turno para profesional 2 (hoy atendido)
        $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Atendido, $this->profesional2);

        // Funcionario ve todo
        $responseFuncionario = $this->actingAs($this->funcionario)->get('/panel');
        $responseFuncionario->assertOk();
        $responseFuncionario->assertSee('Buen día,');
        $responseFuncionario->assertSee('Ver agenda');
        $responseFuncionario->assertSee('Nuevo turno');

        // Profesional 1 solo ve lo suyo
        $responseProf1 = $this->actingAs($this->profesionalUser1)->get('/panel');
        $responseProf1->assertOk();
        $responseProf1->assertSee('Buen día, Ana González');
    }

    public function test_saludos_segun_la_hora(): void
    {
        // 09:00 -> Buen día
        Carbon::setTestNow(Carbon::parse('2026-10-23 09:00:00', config('app.timezone')));
        $this->actingAs($this->funcionario)->get('/panel')->assertSee('Buen día');

        // 14:00 -> Buenas tardes
        Carbon::setTestNow(Carbon::parse('2026-10-23 14:00:00', config('app.timezone')));
        $this->actingAs($this->funcionario)->get('/panel')->assertSee('Buenas tardes');

        // 20:00 -> Buenas noches
        Carbon::setTestNow(Carbon::parse('2026-10-23 20:00:00', config('app.timezone')));
        $this->actingAs($this->funcionario)->get('/panel')->assertSee('Buenas noches');
    }

    public function test_proximos_de_hoy_excluye_cancelados_y_terminados_y_destaca_el_mas_cercano(): void
    {
        // Now = 10:00
        // Terminado (08:00 - 08:30)
        $this->crearTurno('2026-10-23 08:00:00', '2026-10-23 08:30:00', EstadoTurno::Atendido);
        // Cancelado futuro (14:00 - 14:30)
        $this->crearTurno('2026-10-23 14:00:00', '2026-10-23 14:30:00', EstadoTurno::Cancelado);
        // Próximo 1 (11:00)
        $t1 = $this->crearTurno('2026-10-23 11:00:00', '2026-10-23 11:30:00', EstadoTurno::Confirmado);
        // Próximo 2 (16:00)
        $t2 = $this->crearTurno('2026-10-23 16:00:00', '2026-10-23 16:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->funcionario)->get('/panel');
        $response->assertOk();
        $response->assertSee('11:00');
        $response->assertSee('16:00');
        $response->assertDontSee('08:00');
        $response->assertSee(route('panel.turnos.show', $t1));
        $response->assertSee(route('panel.turnos.show', $t2));
    }

    public function test_sin_turnos_proximos_muestra_estado_vacio_con_boton_nuevo(): void
    {
        // Sin turnos para hoy
        $response = $this->actingAs($this->funcionario)->get('/panel');
        $response->assertOk();
        $response->assertSee('No quedan turnos para hoy');
        $response->assertSee(route('panel.turnos.crear'));
    }

    public function test_pendientes_muestra_solicitudes_y_turnos_pasados_sin_marcar(): void
    {
        // 1 solicitud pendiente
        $this->crearTurno('2026-10-23 15:00:00', '2026-10-23 15:30:00', EstadoTurno::Solicitado);

        // 1 turno confirmado pasado sin marcar (ayer)
        $this->crearTurno('2026-10-22 09:00:00', '2026-10-22 09:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->funcionario)->get('/panel');
        $response->assertOk();
        $response->assertSee('1 solicitud por confirmar');
        $response->assertSee('1 turno pasado sin marcar');
    }

    public function test_reenviar_recordatorio_exitoso_y_registra_historial(): void
    {
        Queue::fake();

        $turno = $this->crearTurno('2026-10-23 15:00:00', '2026-10-23 15:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->funcionario)
            ->post("/panel/turnos/{$turno->id}/recordatorio");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Se despacha job
        Queue::assertPushed(EnviarNotificacion::class);

        // Queda entrada en historial
        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'detalle' => 'Recordatorio reenviado',
            'usuario_id' => $this->funcionario->id,
        ]);
    }

    public function test_reenviar_recordatorio_bloquea_si_se_envio_hace_menos_de_15_minutos(): void
    {
        Queue::fake();

        $turno = $this->crearTurno('2026-10-23 15:00:00', '2026-10-23 15:30:00', EstadoTurno::Confirmado);

        // Primer envío a las 10:00
        Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'recordatorio',
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Enviada,
            'enviado_at' => Carbon::parse('2026-10-23 10:00:00', config('app.timezone')),
        ]);

        // Intento a las 10:05 (pasaron 5 min)
        Carbon::setTestNow(Carbon::parse('2026-10-23 10:05:00', config('app.timezone')));

        $resBloqueo = $this->actingAs($this->funcionario)
            ->post("/panel/turnos/{$turno->id}/recordatorio");

        $resBloqueo->assertRedirect();
        $resBloqueo->assertSessionHas('error');
        $this->assertStringContainsString('Ya se envió un recordatorio hace 5 min', session('error'));
        Queue::assertNothingPushed();

        // Intento a las 10:16 (pasaron 16 min) -> debe permitir
        Carbon::setTestNow(Carbon::parse('2026-10-23 10:16:00', config('app.timezone')));

        $resPermitido = $this->actingAs($this->funcionario)
            ->post("/panel/turnos/{$turno->id}/recordatorio");

        $resPermitido->assertRedirect();
        $resPermitido->assertSessionHas('success');
        Queue::assertPushed(EnviarNotificacion::class);
    }

    public function test_reenviar_recordatorio_falla_si_no_esta_confirmado_o_es_de_otra_profesional(): void
    {
        Queue::fake();

        // Turno en solicitado
        $turnoSolicitado = $this->crearTurno('2026-10-23 15:00:00', '2026-10-23 15:30:00', EstadoTurno::Solicitado, $this->profesional1);

        $res1 = $this->actingAs($this->funcionario)
            ->post("/panel/turnos/{$turnoSolicitado->id}/recordatorio");
        $res1->assertSessionHas('error');

        // Profesional 2 intenta recordar turno de profesional 1 -> 403
        $turnoProf1 = $this->crearTurno('2026-10-23 16:00:00', '2026-10-23 16:30:00', EstadoTurno::Confirmado, $this->profesional1);

        $res403 = $this->actingAs($this->profesionalUser2)
            ->post("/panel/turnos/{$turnoProf1->id}/recordatorio");
        $res403->assertForbidden();
    }

    public function test_reenviar_recordatorio_falla_si_el_turno_ya_empezo(): void
    {
        Queue::fake();

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado, $this->profesional1);
        Carbon::setTestNow('2026-10-23 09:10:00');

        $this->actingAs($this->funcionario)
            ->post("/panel/turnos/{$turno->id}/recordatorio")
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }
}
