<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Exceptions\PacienteConTurnoEnHorario;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Un mismo paciente no puede tener dos turnos que se superpongan,
 * aunque sean con profesionales distintas (hallazgo 1 del QA del 28/10).
 */
class TurnoPacienteSinSolapamientoTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE = 'Carlos Gómez ya tiene otro turno en ese horario. Elegí otro.';

    private User $funcionario;

    private User $tutor;

    private Paciente $paciente;

    private Profesional $ana;

    private Profesional $gloria;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->funcionario = User::factory()->funcionario()->create();
        $this->tutor = User::factory()->paciente()->create();
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->tutor->id,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);
        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);

        $this->ana = Profesional::factory()->create(['nombre' => 'Ana', 'apellido' => 'González']);
        $this->gloria = Profesional::factory()->create(['nombre' => 'Gloria', 'apellido' => 'Godoy']);

        foreach ([$this->ana, $this->gloria] as $profesional) {
            $profesional->servicios()->attach($this->servicio->id);
            Disponibilidad::factory()->create([
                'profesional_id' => $profesional->id,
                'consultorio_id' => $this->consultorio->id,
                'dia_semana' => 1,
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

    private function turno(Profesional $profesional, string $inicio, EstadoTurno $estado = EstadoTurno::Confirmado): Turno
    {
        $inicioCarbon = Carbon::parse($inicio, config('app.timezone'));

        return Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicioCarbon,
            'fin' => $inicioCarbon->copy()->addMinutes(30),
            'estado' => $estado,
        ]);
    }

    public function test_la_base_rechaza_dos_turnos_superpuestos_del_mismo_paciente(): void
    {
        $this->turno($this->ana, '2026-10-19 09:00');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('turnos_paciente_sin_solapamiento');

        $this->turno($this->gloria, '2026-10-19 09:15');
    }

    public function test_un_turno_cancelado_no_bloquea_al_paciente(): void
    {
        $this->turno($this->ana, '2026-10-19 09:00', EstadoTurno::Cancelado);
        $this->turno($this->gloria, '2026-10-19 09:00');

        $this->assertSame(2, Turno::count());
    }

    public function test_turnos_contiguos_del_mismo_paciente_pasan(): void
    {
        $this->turno($this->ana, '2026-10-19 09:00');
        $this->turno($this->gloria, '2026-10-19 09:30');

        $this->assertSame(2, Turno::count());
    }

    public function test_el_choque_en_la_base_se_traduce_al_error_del_paciente(): void
    {
        $this->turno($this->ana, '2026-10-19 09:00');

        try {
            DB::transaction(fn () => $this->turno($this->gloria, '2026-10-19 09:00'));
            $this->fail('La base tenía que rechazar el turno.');
        } catch (QueryException $e) {
            $this->assertInstanceOf(PacienteConTurnoEnHorario::class, PacienteConTurnoEnHorario::siSolapamiento($e));
        }
    }

    public function test_recepcion_no_puede_agendar_un_turno_superpuesto_para_el_mismo_paciente(): void
    {
        $this->turno($this->ana, '2026-10-19 09:00');

        $response = $this->actingAs($this->funcionario)->post(route('panel.turnos.store'), [
            'paciente_id' => $this->paciente->id,
            'profesional' => $this->gloria->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
        ]);

        $response->assertRedirect(route('panel.turnos.crear', [
            'paciente' => $this->paciente->id,
            'profesional' => $this->gloria->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
        ]));
        $response->assertSessionHas('error', self::MENSAJE);
        $this->assertSame(0, Turno::where('profesional_id', $this->gloria->id)->count());
    }

    public function test_el_paciente_no_puede_solicitar_un_turno_superpuesto(): void
    {
        $this->turno($this->ana, '2026-10-19 09:00', EstadoTurno::Solicitado);

        $response = $this->actingAs($this->tutor)->post(route('paciente.turnos.store'), [
            'profesional' => $this->gloria->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:15',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertRedirect(route('paciente.reservar', [
            'profesional' => $this->gloria->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'para' => $this->paciente->id,
        ]));
        $response->assertSessionHas('error', self::MENSAJE);
        $this->assertSame(1, Turno::count());

        $follow = $this->get($response->headers->get('Location'));
        $follow->assertOk();
        $follow->assertViewHas('preseleccionado', $this->paciente->id);
        $this->assertMatchesRegularExpression('/value="'.$this->paciente->id.'"[^>]*checked/', $follow->getContent());
    }

    public function test_al_rechazar_solicitud_por_solapamiento_conserva_el_familiar_seleccionado(): void
    {
        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->tutor->id,
            'nombre' => 'Lucía',
            'apellido' => 'Gómez',
        ]);

        $inicio = Carbon::parse('2026-10-19 09:00', config('app.timezone'));
        Turno::factory()->create([
            'paciente_id' => $familiar->id,
            'profesional_id' => $this->ana->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addMinutes(30),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $response = $this->actingAs($this->tutor)->post(route('paciente.turnos.store'), [
            'profesional' => $this->gloria->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:15',
            'paciente_id' => $familiar->id,
        ]);

        $response->assertRedirect(route('paciente.reservar', [
            'profesional' => $this->gloria->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'para' => $familiar->id,
        ]));
        $response->assertSessionHas('error', 'Lucía Gómez ya tiene otro turno en ese horario. Elegí otro.');

        $follow = $this->get($response->headers->get('Location'));
        $follow->assertOk();
        $follow->assertViewHas('preseleccionado', $familiar->id);
        $this->assertMatchesRegularExpression('/value="'.$familiar->id.'"[^>]*checked/', $follow->getContent());
    }

    public function test_no_se_puede_reprogramar_encima_de_otro_turno_del_mismo_paciente(): void
    {
        $turno = $this->turno($this->ana, '2026-10-19 09:00');
        $this->turno($this->gloria, '2026-10-19 10:00');

        $response = $this->actingAs($this->funcionario)->put(route('panel.turnos.reprogramar.update', $turno), [
            'fecha' => '2026-10-19',
            'hora' => '10:00',
        ]);

        $response->assertRedirect(route('panel.turnos.reprogramar', [$turno, 'fecha' => '2026-10-19']));
        $response->assertSessionHas('error', self::MENSAJE);
        $this->assertSame('2026-10-19 09:00', $turno->fresh()->inicio->format('Y-m-d H:i'));
    }

    public function test_reprogramar_sobre_su_propio_horario_no_cuenta_como_choque(): void
    {
        $turno = $this->turno($this->ana, '2026-10-19 09:00');

        $this->actingAs($this->funcionario)->put(route('panel.turnos.reprogramar.update', $turno), [
            'fecha' => '2026-10-19',
            'hora' => '09:15',
        ])->assertRedirect(route('panel.turnos.show', $turno));

        $this->assertSame('2026-10-19 09:15', $turno->fresh()->inicio->format('Y-m-d H:i'));
    }
}
