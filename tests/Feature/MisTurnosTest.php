<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MisTurnosTest extends TestCase
{
    use RefreshDatabase;

    private User $pacienteUser;

    private Paciente $paciente;

    private Paciente $familiar;

    private User $otroPacienteUser;

    private Paciente $otroPaciente;

    private User $profesionalUser;

    private Profesional $profesional;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'carlos@paciente.test']);
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'responsable_id' => null,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $this->familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Sofía',
            'apellido' => 'Gómez',
        ]);

        $this->otroPacienteUser = User::factory()->paciente()->create(['email' => 'juan@paciente.test']);
        $this->otroPaciente = Paciente::factory()->create([
            'user_id' => $this->otroPacienteUser->id,
            'responsable_id' => null,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
        ]);

        $this->profesionalUser = User::factory()->profesional()->create();
        $this->profesional = Profesional::factory()->create([
            'user_id' => $this->profesionalUser->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($this->servicio->id);
    }

    private function crearTurno(Paciente $paciente, string $inicio, string $fin, EstadoTurno $estado, ?string $motivo = null): Turno
    {
        return Turno::create([
            'paciente_id' => $paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => $estado,
            'motivo_cancelacion' => $motivo,
        ]);
    }

    public function test_invitado_redirige_a_login_y_profesional_recibe_403(): void
    {
        $turno = $this->crearTurno($this->paciente, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);

        $this->get(route('paciente.turnos'))->assertRedirect(route('login'));
        $this->post(route('paciente.turnos.cancelar', $turno))->assertRedirect(route('login'));

        $this->actingAs($this->profesionalUser)->get(route('paciente.turnos'))->assertForbidden();
        $this->actingAs($this->profesionalUser)->post(route('paciente.turnos.cancelar', $turno))->assertForbidden();
    }

    public function test_paciente_ve_sus_turnos_y_los_de_su_familiar_pero_no_los_de_otro(): void
    {
        $this->crearTurno($this->paciente, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);
        $this->crearTurno($this->familiar, '2026-10-27 09:00:00', '2026-10-27 09:30:00', EstadoTurno::Solicitado);
        $this->crearTurno($this->otroPaciente, '2026-10-28 11:00:00', '2026-10-28 11:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos'));

        $response->assertOk();
        $response->assertSee('Carlos Gómez');
        $response->assertSee('Sofía Gómez');
        $response->assertSee('Lun 26 oct, 10:00');
        $response->assertSee('Mar 27 oct, 09:00');
        $response->assertDontSee('Juan Pérez');
        $response->assertDontSee('Mié 28 oct, 11:00');
    }

    public function test_separacion_proximos_vs_historial_con_motivo_en_cancelado(): void
    {
        // Próximo
        $this->crearTurno($this->paciente, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);
        // Historial: cancelado futuro
        $this->crearTurno($this->paciente, '2026-10-28 10:00:00', '2026-10-28 10:30:00', EstadoTurno::Cancelado, 'Imprevisto laboral');
        // Historial: pasado atendido
        $this->crearTurno($this->paciente, '2026-10-15 09:00:00', '2026-10-15 09:30:00', EstadoTurno::Atendido);

        // Pestaña Próximos
        $resProximos = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos'));
        $resProximos->assertOk();
        $resProximos->assertSee('Lun 26 oct, 10:00');
        $resProximos->assertSee('Próximos (1)');
        $resProximos->assertDontSee('Imprevisto laboral');
        $resProximos->assertDontSee('Jue 15 oct, 09:00');

        // Pestaña Historial
        $resHistorial = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos', ['ver' => 'historial']));
        $resHistorial->assertOk();
        $resHistorial->assertSee('Imprevisto laboral');
        $resHistorial->assertSee('Jue 15 oct, 09:00');
        $resHistorial->assertDontSee('Lun 26 oct, 10:00');
    }

    public function test_cancelar_propio_solicitado_y_confirmado_con_mas_de_24_horas(): void
    {
        // 48 horas en el futuro (> 24 h)
        $turnoSolicitado = $this->crearTurno($this->paciente, '2026-10-20 12:00:00', '2026-10-20 12:30:00', EstadoTurno::Solicitado);
        $turnoConfirmado = $this->crearTurno($this->familiar, '2026-10-20 15:00:00', '2026-10-20 15:30:00', EstadoTurno::Confirmado);

        $res1 = $this->actingAs($this->pacienteUser)->post(route('paciente.turnos.cancelar', $turnoSolicitado));
        $res1->assertRedirect(route('paciente.turnos'));
        $res1->assertSessionHas('success');
        $this->assertSame(EstadoTurno::Cancelado, $turnoSolicitado->fresh()->estado);

        $res2 = $this->actingAs($this->pacienteUser)->post(route('paciente.turnos.cancelar', $turnoConfirmado));
        $res2->assertRedirect(route('paciente.turnos'));
        $res2->assertSessionHas('success');
        $this->assertSame(EstadoTurno::Cancelado, $turnoConfirmado->fresh()->estado);
    }

    public function test_no_cancela_con_menos_de_24_horas_ni_en_estado_atendido(): void
    {
        // 2 horas en el futuro (< 24 h)
        $turnoProximo = $this->crearTurno($this->paciente, '2026-10-18 14:00:00', '2026-10-18 14:30:00', EstadoTurno::Confirmado);
        // Atendido
        $turnoAtendido = $this->crearTurno($this->paciente, '2026-10-15 09:00:00', '2026-10-15 09:30:00', EstadoTurno::Atendido);

        $res1 = $this->actingAs($this->pacienteUser)->post(route('paciente.turnos.cancelar', $turnoProximo));
        $res1->assertRedirect(route('paciente.turnos'));
        $res1->assertSessionHas('error');
        $this->assertSame(EstadoTurno::Confirmado, $turnoProximo->fresh()->estado);

        $res2 = $this->actingAs($this->pacienteUser)->post(route('paciente.turnos.cancelar', $turnoAtendido));
        $res2->assertRedirect(route('paciente.turnos'));
        $res2->assertSessionHas('error');
        $this->assertSame(EstadoTurno::Atendido, $turnoAtendido->fresh()->estado);
    }

    public function test_no_cancela_turno_de_otro_paciente_recibe_403(): void
    {
        $turnoAjeno = $this->crearTurno($this->otroPaciente, '2026-10-25 10:00:00', '2026-10-25 10:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->pacienteUser)->post(route('paciente.turnos.cancelar', $turnoAjeno));
        $response->assertForbidden();

        $this->assertSame(EstadoTurno::Confirmado, $turnoAjeno->fresh()->estado);
    }

    public function test_cancelar_no_despacha_notificacion_y_registra_historial(): void
    {
        Queue::fake();

        $turno = $this->crearTurno($this->paciente, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->pacienteUser)->post(route('paciente.turnos.cancelar', $turno));
        $response->assertRedirect(route('paciente.turnos'));

        Queue::assertNothingPushed();

        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'estado_anterior' => EstadoTurno::Confirmado->value,
            'estado_nuevo' => EstadoTurno::Cancelado->value,
            'usuario_id' => $this->pacienteUser->id,
            'detalle' => 'Cancelado por el paciente',
        ]);
    }

    public function test_empty_state_en_ambas_pestanas(): void
    {
        $resProximos = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos'));
        $resProximos->assertOk();
        $resProximos->assertSee(route('paciente.reservar'));

        $resHistorial = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos', ['ver' => 'historial']));
        $resHistorial->assertOk();
        $resHistorial->assertSee(route('paciente.reservar'));
    }

    public function test_item_mis_turnos_aparece_en_el_menu_del_paciente(): void
    {
        $response = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos'));
        $response->assertOk();
        $response->assertSee('Mis turnos');
    }
}
