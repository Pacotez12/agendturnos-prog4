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
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadosTurnoTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $profesionalUser1;

    private Profesional $profesional1;

    private User $profesionalUser2;

    private Profesional $profesional2;

    private Paciente $paciente;

    private Consultorio $consultorio;

    private Servicio $servicio30;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->funcionario = User::factory()->funcionario()->create(['email' => 'admin@glowdental.test']);

        $this->profesionalUser1 = User::factory()->profesional()->create(['email' => 'dra.ana@glowdental.test']);
        $this->profesional1 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser1->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->profesionalUser2 = User::factory()->profesional()->create(['email' => 'dra.gloria@glowdental.test']);
        $this->profesional2 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser2->id,
            'nombre' => 'Gloria',
            'apellido' => 'Godoy',
        ]);

        $this->paciente = Paciente::factory()->create([
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio30 = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);

        $this->profesional1->servicios()->attach($this->servicio30->id);
        $this->profesional2->servicios()->attach($this->servicio30->id);

        // Disponibilidad: Lunes (1) y Viernes (5) de 08:00 a 12:00
        foreach ([$this->profesional1, $this->profesional2] as $prof) {
            Disponibilidad::factory()->create([
                'profesional_id' => $prof->id,
                'consultorio_id' => $this->consultorio->id,
                'dia_semana' => 1,
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
            ]);
            Disponibilidad::factory()->create([
                'profesional_id' => $prof->id,
                'consultorio_id' => $this->consultorio->id,
                'dia_semana' => 5,
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearTurno(
        Profesional $prof,
        string $inicio,
        string $fin,
        EstadoTurno $estado = EstadoTurno::Solicitado
    ): Turno {
        return Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $prof->id,
            'servicio_id' => $this->servicio30->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse($inicio, config('app.timezone')),
            'fin' => Carbon::parse($fin, config('app.timezone')),
            'estado' => $estado,
        ]);
    }

    public function test_confirmar_turno_crea_historial_y_actualiza_estado(): void
    {
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00');

        $response = $this->actingAs($this->profesionalUser1)
            ->post(route('panel.turnos.confirmar', $turno));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Turno confirmado.');

        $this->assertDatabaseHas('turnos', [
            'id' => $turno->id,
            'estado' => EstadoTurno::Confirmado->value,
        ]);

        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'estado_anterior' => EstadoTurno::Solicitado->value,
            'estado_nuevo' => EstadoTurno::Confirmado->value,
            'usuario_id' => $this->profesionalUser1->id,
        ]);
    }

    public function test_cancelar_turno_guarda_motivo_y_libera_el_horario(): void
    {
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->profesionalUser1)
            ->post(route('panel.turnos.cancelar', $turno), [
                'motivo' => 'La doctora no atiende ese día',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Turno cancelado.');

        $this->assertDatabaseHas('turnos', [
            'id' => $turno->id,
            'estado' => EstadoTurno::Cancelado->value,
            'motivo_cancelacion' => 'La doctora no atiende ese día',
        ]);

        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'estado_anterior' => EstadoTurno::Confirmado->value,
            'estado_nuevo' => EstadoTurno::Cancelado->value,
            'usuario_id' => $this->profesionalUser1->id,
        ]);

        // Verificamos que el horario esté libre ahora
        $dispService = app(DisponibilidadService::class);
        $this->assertTrue($dispService->estaLibre(
            $this->profesional1,
            $this->servicio30,
            Carbon::parse('2026-10-19 09:00:00', config('app.timezone'))
        ));
    }

    public function test_marcar_atendido_antes_de_que_empiece_falla(): void
    {
        // Turno para mañana
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->profesionalUser1)
            ->post(route('panel.turnos.atendido', $turno));

        $response->assertSessionHas('error');
        $this->assertSame(EstadoTurno::Confirmado, $turno->fresh()->estado);
    }

    public function test_marcar_atendido_desde_solicitado_falla(): void
    {
        // Turno en el pasado pero en estado Solicitado
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Solicitado);

        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', config('app.timezone')));

        $response = $this->actingAs($this->profesionalUser1)
            ->post(route('panel.turnos.atendido', $turno));

        $response->assertSessionHas('error');
        $this->assertSame(EstadoTurno::Solicitado, $turno->fresh()->estado);
    }

    public function test_marcar_atendido_y_ausente_exitosos_cuando_turno_ya_empezo(): void
    {
        $turno1 = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Confirmado);
        $turno2 = $this->crearTurno($this->profesional1, '2026-10-19 09:30:00', '2026-10-19 10:00:00', EstadoTurno::Confirmado);

        // Simulamos que ya son las 09:45
        Carbon::setTestNow(Carbon::parse('2026-10-19 09:45:00', config('app.timezone')));

        $res1 = $this->actingAs($this->profesionalUser1)->post(route('panel.turnos.atendido', $turno1));
        $res1->assertSessionHas('success', 'Turno marcado como atendido.');
        $this->assertSame(EstadoTurno::Atendido, $turno1->fresh()->estado);

        $res2 = $this->actingAs($this->profesionalUser1)->post(route('panel.turnos.ausente', $turno2));
        $res2->assertSessionHas('success', 'Turno marcado como ausente.');
        $this->assertSame(EstadoTurno::Ausente, $turno2->fresh()->estado);
    }

    public function test_reprogramar_mueve_el_turno_y_deja_detalle_en_historial(): void
    {
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->profesionalUser1)
            ->put(route('panel.turnos.reprogramar.update', $turno), [
                'fecha' => '2026-10-19',
                'hora' => '10:00',
            ]);

        $response->assertRedirect(route('panel.turnos.show', $turno));
        $response->assertSessionHas('success', 'Turno reprogramado.');

        $turno->refresh();
        $this->assertSame('2026-10-19 10:00', $turno->inicio->format('Y-m-d H:i'));
        $this->assertSame('2026-10-19 10:30', $turno->fin->format('Y-m-d H:i'));
        $this->assertSame(EstadoTurno::Confirmado, $turno->estado);

        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'estado_anterior' => EstadoTurno::Confirmado->value,
            'estado_nuevo' => EstadoTurno::Confirmado->value,
            'usuario_id' => $this->profesionalUser1->id,
            'detalle' => 'Reprogramado del 19/10 09:00 al 19/10 10:00',
        ]);
    }

    public function test_reprogramar_permite_correr_turno_15_minutos_sobre_si_mismo(): void
    {
        // Turno de 09:00 a 09:30. Se mueve a 09:15 a 09:45 (se superpone con sus propios 15 min)
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->profesionalUser1)
            ->put(route('panel.turnos.reprogramar.update', $turno), [
                'fecha' => '2026-10-19',
                'hora' => '09:15',
            ]);

        $response->assertRedirect(route('panel.turnos.show', $turno));
        $this->assertSame('2026-10-19 09:15', $turno->fresh()->inicio->format('Y-m-d H:i'));
    }

    public function test_reprogramar_a_horario_ocupado_falla_y_no_cambia(): void
    {
        $turno1 = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Confirmado);
        $this->crearTurno($this->profesional1, '2026-10-19 10:00:00', '2026-10-19 10:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->profesionalUser1)
            ->put(route('panel.turnos.reprogramar.update', $turno1), [
                'fecha' => '2026-10-19',
                'hora' => '10:00',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');
        $this->assertSame('2026-10-19 09:00', $turno1->fresh()->inicio->format('Y-m-d H:i'));
    }

    public function test_profesional_no_puede_ver_ni_operar_turnos_de_otra_profesional(): void
    {
        // Turno de la profesional 2
        $turno2 = $this->crearTurno($this->profesional2, '2026-10-19 09:00:00', '2026-10-19 09:30:00');

        // Profesional 1 intenta ver y operar
        $this->actingAs($this->profesionalUser1)->get(route('panel.turnos.show', $turno2))->assertForbidden();
        $this->actingAs($this->profesionalUser1)->post(route('panel.turnos.confirmar', $turno2))->assertForbidden();
        $this->actingAs($this->profesionalUser1)->post(route('panel.turnos.cancelar', $turno2))->assertForbidden();
        $this->actingAs($this->profesionalUser1)->get(route('panel.turnos.reprogramar', $turno2))->assertForbidden();
        $this->actingAs($this->profesionalUser1)->put(route('panel.turnos.reprogramar.update', $turno2), ['fecha' => '2026-10-19', 'hora' => '10:00'])->assertForbidden();

        // Funcionario sí puede
        $this->actingAs($this->funcionario)->get(route('panel.turnos.show', $turno2))->assertOk();
    }

    public function test_paciente_recibe_403_en_todas_las_rutas_del_panel(): void
    {
        $pacienteUser = $this->paciente->user;
        $turno = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00');

        $this->actingAs($pacienteUser)->get(route('panel.solicitudes'))->assertForbidden();
        $this->actingAs($pacienteUser)->get(route('panel.turnos.show', $turno))->assertForbidden();
        $this->actingAs($pacienteUser)->post(route('panel.turnos.confirmar', $turno))->assertForbidden();
    }

    public function test_solicitudes_lista_solo_solicitados_futuros_y_filtra_por_profesional(): void
    {
        // 1. Solicitado futuro prof 1
        $t1 = $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Solicitado);
        // 2. Solicitado futuro prof 2
        $t2 = $this->crearTurno($this->profesional2, '2026-10-19 10:00:00', '2026-10-19 10:30:00', EstadoTurno::Solicitado);
        // 3. Confirmado futuro prof 1 (no debe aparecer)
        $t3 = $this->crearTurno($this->profesional1, '2026-10-19 11:00:00', '2026-10-19 11:30:00', EstadoTurno::Confirmado);
        // 4. Solicitado pasado prof 1 (no debe aparecer)
        $t4 = $this->crearTurno($this->profesional1, '2026-10-17 09:00:00', '2026-10-17 09:30:00', EstadoTurno::Solicitado);

        // Profesional 1 ve solo t1
        $resProf1 = $this->actingAs($this->profesionalUser1)->get(route('panel.solicitudes'));
        $resProf1->assertOk();
        $resProf1->assertSee('Solicitudes por confirmar');
        $resProf1->assertSee($t1->paciente->nombre);
        $resProf1->assertDontSee($t2->profesional->nombre);
        $resProf1->assertDontSee('11:00');

        // Funcionario ve t1 y t2 y ve la columna Profesional
        $resFunc = $this->actingAs($this->funcionario)->get(route('panel.solicitudes'));
        $resFunc->assertOk();
        $resFunc->assertSee('Profesional');
        $resFunc->assertSee('Ana González');
        $resFunc->assertSee('Gloria Godoy');
    }

    public function test_contador_en_la_barra_lateral_coincide_con_solicitudes(): void
    {
        $this->crearTurno($this->profesional1, '2026-10-19 09:00:00', '2026-10-19 09:30:00', EstadoTurno::Solicitado);
        $this->crearTurno($this->profesional1, '2026-10-19 10:00:00', '2026-10-19 10:30:00', EstadoTurno::Solicitado);
        $this->crearTurno($this->profesional2, '2026-10-19 11:00:00', '2026-10-19 11:30:00', EstadoTurno::Solicitado);

        // Profesional 1 debe ver badge con 2
        $resProf = $this->actingAs($this->profesionalUser1)->get(route('panel.solicitudes'));
        $resProf->assertSee('>2<', false);

        // Funcionario debe ver badge con 3
        $resFunc = $this->actingAs($this->funcionario)->get(route('panel.solicitudes'));
        $resFunc->assertSee('>3<', false);
    }
}
