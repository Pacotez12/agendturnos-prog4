<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Bloqueo;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReservarTurnoTest extends TestCase
{
    use RefreshDatabase;

    private User $paciente;

    private Profesional $profesional;

    private Consultorio $consultorio;

    private Servicio $servicio30;

    private Servicio $servicio60;

    protected function setUp(): void
    {
        parent::setUp();

        // Domingo 18/10/2026 a las 12:00; el lunes 19/10 es el próximo día hábil
        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->paciente = Paciente::factory()->create()->user;
        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $this->profesional = Profesional::factory()->create(['nombre' => 'Ana', 'apellido' => 'González']);
        $this->servicio30 = Servicio::factory()->create(['nombre' => 'Consulta general', 'duracion_min' => 30]);
        $this->servicio60 = Servicio::factory()->create(['nombre' => 'Limpieza profunda', 'duracion_min' => 60]);
        $this->profesional->servicios()->attach([$this->servicio30->id, $this->servicio60->id]);

        // Lunes de 08:00 a 12:00
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
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_invitado_es_redirigido_y_profesional_recibe_403(): void
    {
        $this->get('/turnos/nuevo')->assertRedirect(route('login'));

        $this->actingAs($this->profesional->user)->get('/turnos/nuevo')->assertForbidden();
    }

    public function test_paciente_ve_las_profesionales(): void
    {
        Profesional::factory()->create(['nombre' => 'Laura', 'apellido' => 'Benítez']);

        $response = $this->actingAs($this->paciente)->get('/turnos/nuevo');

        $response->assertOk();
        $response->assertSee('Ana González');
        $response->assertSee('Laura Benítez');
    }

    public function test_solo_se_listan_los_servicios_activos_de_la_profesional_elegida(): void
    {
        $inactivo = Servicio::factory()->create(['nombre' => 'Blanqueamiento', 'activo' => false]);
        $this->profesional->servicios()->attach($inactivo->id);

        $otra = Profesional::factory()->create();
        $deOtra = Servicio::factory()->create(['nombre' => 'Ortodoncia']);
        $otra->servicios()->attach($deOtra->id);

        $response = $this->actingAs($this->paciente)
            ->get('/turnos/nuevo?profesional='.$this->profesional->id);

        $response->assertOk();
        $response->assertSee('Consulta general');
        $response->assertSee('Limpieza profunda');
        $response->assertDontSee('Blanqueamiento');
        $response->assertDontSee('Ortodoncia');
    }

    public function test_servicio_que_la_profesional_no_ofrece_falla(): void
    {
        $ajeno = Servicio::factory()->create();

        $response = $this->actingAs($this->paciente)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $ajeno->id,
        ]));

        $response->assertSessionHasErrors('servicio');
    }

    public function test_fecha_pasada_falla(): void
    {
        $response = $this->actingAs($this->paciente)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio30->id,
            'fecha' => '2026-10-17',
        ]));

        $response->assertSessionHasErrors('fecha');
    }

    public function test_turno_confirmado_ocupa_el_horario_y_turno_cancelado_no(): void
    {
        $this->crearTurno('09:00', '09:30', EstadoTurno::Confirmado);
        $this->crearTurno('10:00', '10:30', EstadoTurno::Cancelado);

        $response = $this->consultarLunes($this->servicio30);

        $response->assertOk();
        $horarios = $this->horarios($response);

        $this->assertTrue($horarios['08:30']);
        $this->assertFalse($horarios['09:00']);
        $this->assertTrue($horarios['10:00']);
    }

    public function test_horario_dentro_de_un_bloqueo_no_esta_libre(): void
    {
        Bloqueo::factory()->create([
            'profesional_id' => $this->profesional->id,
            'inicio' => Carbon::parse('2026-10-19 11:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 12:00', config('app.timezone')),
        ]);

        $horarios = $this->horarios($this->consultarLunes($this->servicio30));

        $this->assertTrue($horarios['10:30']);
        $this->assertFalse($horarios['11:00']);
        $this->assertFalse($horarios['11:30']);
    }

    public function test_servicio_de_60_minutos_no_entra_en_un_hueco_de_30(): void
    {
        // Libre solo de 08:30 a 09:00
        $this->crearTurno('08:00', '08:30', EstadoTurno::Confirmado);
        $this->crearTurno('09:00', '12:00', EstadoTurno::Confirmado);

        $horarios30 = $this->horarios($this->consultarLunes($this->servicio30));
        $horarios60 = $this->horarios($this->consultarLunes($this->servicio60));

        $this->assertTrue($horarios30['08:30']);
        $this->assertNotContains(true, $horarios60);
    }

    public function test_dia_sin_atencion_muestra_mensaje(): void
    {
        // Martes 20/10: la profesional solo atiende los lunes
        $response = $this->actingAs($this->paciente)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio30->id,
            'fecha' => '2026-10-20',
        ]));

        $response->assertOk();
        $response->assertSee('No hay horarios');
    }

    private function consultarLunes(Servicio $servicio): TestResponse
    {
        return $this->actingAs($this->paciente)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $servicio->id,
            'fecha' => '2026-10-19',
        ]));
    }

    /**
     * @return array<string, bool> hora => libre
     */
    private function horarios(TestResponse $response): array
    {
        return array_column($response->viewData('horarios'), 'libre', 'hora');
    }

    private function crearTurno(string $desde, string $hasta, EstadoTurno $estado): void
    {
        Turno::factory()->create([
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio30->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 '.$desde, config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 '.$hasta, config('app.timezone')),
            'estado' => $estado,
        ]);
    }
}
