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
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $profesionalUser1;

    private Profesional $profesional1;

    private User $profesionalUser2;

    private Profesional $profesional2;

    private User $pacienteUser;

    private Paciente $paciente1;

    private Paciente $paciente2;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-26 12:00:00', config('app.timezone'))); // Lunes 26 oct 2026

        $this->funcionario = User::factory()->funcionario()->create(['email' => 'admin@glowdental.test']);

        $this->profesionalUser1 = User::factory()->profesional()->create();
        $this->profesional1 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser1->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->profesionalUser2 = User::factory()->profesional()->create();
        $this->profesional2 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser2->id,
            'nombre' => 'Gloria',
            'apellido' => 'Godoy',
        ]);

        $this->pacienteUser = User::factory()->paciente()->create();
        $this->paciente1 = Paciente::factory()->create([
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);
        $this->paciente2 = Paciente::factory()->create([
            'nombre' => 'Lucía',
            'apellido' => 'Benítez',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional1->servicios()->attach($this->servicio->id);
        $this->profesional2->servicios()->attach($this->servicio->id);

        // Disponibilidad lunes de 08:00 a 14:00
        foreach ([$this->profesional1, $this->profesional2] as $prof) {
            Disponibilidad::factory()->create([
                'profesional_id' => $prof->id,
                'consultorio_id' => $this->consultorio->id,
                'dia_semana' => 1,
                'hora_inicio' => '08:00',
                'hora_fin' => '14:00',
            ]);
        }
    }

    private function crearTurno(Profesional $prof, Paciente $paciente, string $inicio, string $fin, EstadoTurno $estado): Turno
    {
        return Turno::create([
            'paciente_id' => $paciente->id,
            'profesional_id' => $prof->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => $estado,
        ]);
    }

    public function test_invitado_redirige_a_login_y_paciente_recibe_403(): void
    {
        $this->get(route('panel.agenda'))->assertRedirect(route('login'));
        $this->actingAs($this->pacienteUser)->get(route('panel.agenda'))->assertForbidden();
    }

    public function test_profesional_ve_sus_turnos_del_dia_y_no_los_de_otra(): void
    {
        $turnoAna = $this->crearTurno($this->profesional1, $this->paciente1, '2026-10-26 09:00:00', '2026-10-26 09:30:00', EstadoTurno::Confirmado);
        $turnoGloria = $this->crearTurno($this->profesional2, $this->paciente2, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
        ]));

        $response->assertOk();
        $response->assertSee('Carlos Gómez');
        $response->assertSee('Ana González');
        $response->assertDontSee('Lucía Benítez');
        $response->assertDontSee('Gloria Godoy');
    }

    public function test_funcionario_ve_las_dos_columnas_en_vista_dia(): void
    {
        $this->crearTurno($this->profesional1, $this->paciente1, '2026-10-26 09:00:00', '2026-10-26 09:30:00', EstadoTurno::Confirmado);
        $this->crearTurno($this->profesional2, $this->paciente2, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->funcionario)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
        ]));

        $response->assertOk();
        $response->assertSee('Ana González');
        $response->assertSee('Gloria Godoy');
        $response->assertSee('Carlos Gómez');
        $response->assertSee('Lucía Benítez');
    }

    public function test_cancelados_ocultos_por_defecto_y_visibles_con_filtro(): void
    {
        $turnoConfirmado = $this->crearTurno($this->profesional1, $this->paciente1, '2026-10-26 09:00:00', '2026-10-26 09:30:00', EstadoTurno::Confirmado);
        $turnoCancelado = $this->crearTurno($this->profesional1, $this->paciente2, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Cancelado);

        // Sin filtro de estado: cancelados ocultos
        $resSinFiltro = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
        ]));
        $resSinFiltro->assertOk();
        $resSinFiltro->assertSee('Carlos Gómez');
        $resSinFiltro->assertDontSee('Lucía Benítez');

        // Con filtro estado=cancelado: visible
        $resConFiltro = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
            'estado' => 'cancelado',
        ]));
        $resConFiltro->assertOk();
        $resConFiltro->assertSee('Lucía Benítez');
        $resConFiltro->assertDontSee('Carlos Gómez');
    }

    public function test_filtro_por_estado(): void
    {
        $this->crearTurno($this->profesional1, $this->paciente1, '2026-10-26 09:00:00', '2026-10-26 09:30:00', EstadoTurno::Solicitado);
        $this->crearTurno($this->profesional1, $this->paciente2, '2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);

        $resSolicitado = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
            'estado' => 'solicitado',
        ]));
        $resSolicitado->assertOk();
        $resSolicitado->assertSee('Carlos Gómez');
        $resSolicitado->assertDontSee('Lucía Benítez');
    }

    public function test_navegacion_de_fecha_anterior_y_siguiente(): void
    {
        $response = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
        ]));

        $response->assertOk();
        // Anterior -> 2026-10-25
        $response->assertSee('fecha=2026-10-25');
        // Siguiente -> 2026-10-27
        $response->assertSee('fecha=2026-10-27');
    }

    public function test_semana_muestra_lunes_a_viernes_y_fuerza_profesional(): void
    {
        $turnoAna = $this->crearTurno($this->profesional1, $this->paciente1, '2026-10-28 09:00:00', '2026-10-28 09:30:00', EstadoTurno::Confirmado);
        $turnoGloria = $this->crearTurno($this->profesional2, $this->paciente2, '2026-10-28 10:00:00', '2026-10-28 10:30:00', EstadoTurno::Confirmado);

        // Profesional 1 intenta pasar profesional=profesional2 en query
        $response = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'vista' => 'semana',
            'fecha' => '2026-10-26',
            'profesional' => $this->profesional2->id,
        ]));

        $response->assertOk();
        $response->assertSee('Lunes');
        $response->assertSee('Viernes');
        $response->assertSee('Carlos Gómez');
        // Debe haberse forzado profesional1, por lo que no ve el turno de Gloria
        $response->assertDontSee('Lucía Benítez');

        // Funcionario en semana puede elegir profesional
        $resFunc = $this->actingAs($this->funcionario)->get(route('panel.agenda', [
            'vista' => 'semana',
            'fecha' => '2026-10-26',
            'profesional' => $this->profesional2->id,
        ]));
        $resFunc->assertOk();
        $resFunc->assertSee('Lucía Benítez');
        $resFunc->assertDontSee('Carlos Gómez');
    }

    public function test_por_confirmar_muestra_solicitados_pendientes(): void
    {
        // Turno solicitado futuro
        $this->crearTurno($this->profesional1, $this->paciente1, '2026-10-27 10:00:00', '2026-10-27 10:30:00', EstadoTurno::Solicitado);

        $response = $this->actingAs($this->profesionalUser1)->get(route('panel.agenda', [
            'fecha' => '2026-10-26',
        ]));

        $response->assertOk();
        $response->assertSee('Por confirmar');
        $response->assertSee('Carlos Gómez');
        $response->assertSee(route('panel.turnos.confirmar', Turno::where('estado', EstadoTurno::Solicitado)->first()));
    }
}
