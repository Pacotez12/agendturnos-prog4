<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Exceptions\HorarioNoDisponible;
use App\Exceptions\TransicionInvalida;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\DisponibilidadService;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HallazgosAgendaTest extends TestCase
{
    use RefreshDatabase;

    private Profesional $profesional;

    private Servicio $servicio;

    private Consultorio $consultorio;

    private User $funcionario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-18 12:00:00'));
        $this->profesional = Profesional::factory()->create();
        $this->servicio = Servicio::factory()->create(['duracion_min' => 60]);
        $this->profesional->servicios()->attach($this->servicio);
        $this->consultorio = Consultorio::factory()->create();
        $this->funcionario = User::factory()->funcionario()->create();
    }

    private function franja(string $desde, string $hasta, ?Consultorio $consultorio = null): void
    {
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => ($consultorio ?? $this->consultorio)->id,
            'dia_semana' => 1,
            'hora_inicio' => $desde,
            'hora_fin' => $hasta,
        ]);
    }

    public function test_p04_rechaza_fin_del_dia_siguiente_y_admite_fin_exacto_de_franja(): void
    {
        $this->franja('22:00', '23:30');
        $service = app(DisponibilidadService::class);
        $this->assertTrue($service->estaLibre($this->profesional, $this->servicio, Carbon::parse('2026-10-19 22:30')));
        $this->assertFalse($service->estaLibre($this->profesional, $this->servicio, Carbon::parse('2026-10-19 23:15')));
    }

    public function test_p04_reserva_directa_no_puede_cruzar_medianoche(): void
    {
        $this->franja('22:00', '23:30');
        $this->expectException(HorarioNoDisponible::class);
        app(ReservaService::class)->agendar($this->profesional, $this->servicio, Paciente::factory()->create(), Carbon::parse('2026-10-19 23:15'), $this->funcionario);
    }

    public function test_p04_consultorio_corresponde_a_franja_que_contiene_todo_el_turno(): void
    {
        $this->franja('08:00', '09:00');
        $otro = Consultorio::factory()->create();
        $this->franja('08:00', '12:00', $otro);
        $turno = app(ReservaService::class)->agendar($this->profesional, $this->servicio, Paciente::factory()->create(), Carbon::parse('2026-10-19 08:45'), $this->funcionario);
        $this->assertSame($otro->id, $turno->consultorio_id);
    }

    public static function estadosFinales(): array
    {
        return [[EstadoTurno::Cancelado], [EstadoTurno::Atendido], [EstadoTurno::Ausente]];
    }

    #[DataProvider('estadosFinales')]
    public function test_p07_rechaza_modelo_obsoleto_si_el_turno_paso_a_estado_final(EstadoTurno $estado): void
    {
        $this->franja('08:00', '12:00');
        $turno = Turno::factory()->create([
            'profesional_id' => $this->profesional->id, 'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id, 'inicio' => '2026-10-19 08:00', 'fin' => '2026-10-19 09:00',
            'estado' => EstadoTurno::Confirmado,
        ]);
        Turno::whereKey($turno->id)->update(['estado' => $estado]);
        try {
            app(AgendaService::class)->reprogramar($turno, Carbon::parse('2026-10-19 10:00'), $this->funcionario);
            $this->fail('No debe reprogramar un turno en estado final.');
        } catch (TransicionInvalida) {
            $this->assertSame($estado, $turno->fresh()->estado);
            $this->assertSame('08:00', $turno->fresh()->inicio->format('H:i'));
            $this->assertSame(0, $turno->historial()->count());
            $this->assertDatabaseCount('notificaciones', 0);
        }
    }

    public function test_p07_usa_inicio_y_estado_actuales_en_historial(): void
    {
        $this->franja('08:00', '12:00');
        $turno = Turno::factory()->create([
            'profesional_id' => $this->profesional->id, 'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id, 'inicio' => '2026-10-19 08:00', 'fin' => '2026-10-19 09:00',
            'estado' => EstadoTurno::Solicitado,
        ]);
        Turno::whereKey($turno->id)->update(['estado' => EstadoTurno::Confirmado, 'inicio' => '2026-10-19 09:00', 'fin' => '2026-10-19 10:00']);
        $nuevo = app(AgendaService::class)->reprogramar($turno, Carbon::parse('2026-10-19 10:00'), $this->funcionario);
        $historial = $nuevo->historial()->sole();
        $this->assertSame(EstadoTurno::Confirmado, $historial->estado_anterior);
        $this->assertSame(EstadoTurno::Confirmado, $historial->estado_nuevo);
        $this->assertStringContainsString('19/10 09:00', $historial->detalle);
    }

    public function test_p07_compara_el_nuevo_inicio_con_el_persistido(): void
    {
        $this->franja('08:00', '12:00');
        $turno = Turno::factory()->create([
            'profesional_id' => $this->profesional->id, 'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id, 'inicio' => '2026-10-19 08:00', 'fin' => '2026-10-19 09:00',
        ]);
        Turno::whereKey($turno->id)->update(['inicio' => '2026-10-19 10:00', 'fin' => '2026-10-19 11:00']);
        $this->expectException(TransicionInvalida::class);
        $this->expectExceptionMessage('El turno ya está en ese horario.');
        app(AgendaService::class)->reprogramar($turno, Carbon::parse('2026-10-19 10:00'), $this->funcionario);
    }

    public function test_p09_formulario_envia_intencion_explicita_y_permite_vaciar_horario(): void
    {
        $this->franja('08:00', '12:00');
        $this->actingAs($this->funcionario)->get(route('panel.disponibilidad', ['profesional_id' => $this->profesional->id]))
            ->assertSee('name="guardar_horario"', false);
        $this->put(route('panel.disponibilidad.horario'), ['profesional_id' => $this->profesional->id, 'guardar_horario' => '1'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(0, $this->profesional->disponibilidades()->count());
    }

    public function test_p09_sin_marcador_o_con_franjas_malformadas_no_borra_horario(): void
    {
        $this->franja('08:00', '12:00');
        foreach ([[], ['guardar_horario' => '1', 'franjas' => 'incorrecto'], ['guardar_horario' => '1', 'franjas' => null], ['guardar_horario' => '1', 'franjas' => [['hora_inicio' => '08:00']]]] as $datos) {
            $this->actingAs($this->funcionario)->put(route('panel.disponibilidad.horario'), ['profesional_id' => $this->profesional->id, ...$datos])
                ->assertSessionHasErrors();
            $this->assertSame(1, $this->profesional->disponibilidades()->count());
        }
    }
}
