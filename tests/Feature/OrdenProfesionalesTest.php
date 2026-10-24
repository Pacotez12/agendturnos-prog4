<?php

namespace Tests\Feature;

use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenProfesionalesTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private Profesional $profesionalPrimero;

    private Profesional $profesionalSegundo;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->funcionario = User::factory()->funcionario()->create();

        Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Servicio General',
            'activo' => true,
        ]);

        // Profesional con apellido alfabéticamente posterior pero orden menor
        $user1 = User::factory()->profesional()->create();
        $this->profesionalPrimero = Profesional::factory()->create([
            'user_id' => $user1->id,
            'nombre' => 'Bernardo',
            'apellido' => 'Zapata',
            'orden' => 1,
        ]);

        // Profesional con apellido alfabéticamente anterior pero orden mayor
        $user2 = User::factory()->profesional()->create();
        $this->profesionalSegundo = Profesional::factory()->create([
            'user_id' => $user2->id,
            'nombre' => 'Alberto',
            'apellido' => 'Alvarez',
            'orden' => 2,
        ]);

        $this->profesionalPrimero->servicios()->attach($this->servicio->id);
        $this->profesionalSegundo->servicios()->attach($this->servicio->id);
    }

    public function test_opciones_de_reserva_respeta_orden(): void
    {
        $opciones = app(ReservaService::class)->opciones(null, null, null);

        $this->assertCount(2, $opciones['profesionales']);
        $this->assertSame($this->profesionalPrimero->id, $opciones['profesionales']->first()->id);
        $this->assertSame($this->profesionalSegundo->id, $opciones['profesionales']->last()->id);
    }

    public function test_agenda_columnas_del_dia_respeta_orden(): void
    {
        $datos = app(AgendaService::class)->agendaDelDia($this->funcionario, now());

        $this->assertCount(2, $datos['profesionales']);
        $this->assertSame($this->profesionalPrimero->id, $datos['profesionales']->first()->id);
        $this->assertSame($this->profesionalSegundo->id, $datos['profesionales']->last()->id);
    }

    public function test_disponibilidad_default_y_selector_respeta_orden(): void
    {
        $response = $this->actingAs($this->funcionario)->get('/panel/disponibilidad');

        $response->assertOk();
        // Profesional por defecto debe ser el primero según el orden
        $response->assertSee('Bernardo Zapata');
        // El orden en el selector debe mostrar primero al de menor orden
        $posicionPrimero = strpos($response->getContent(), 'Bernardo Zapata');
        $posicionSegundo = strpos($response->getContent(), 'Alberto Alvarez');

        $this->assertNotFalse($posicionPrimero);
        $this->assertNotFalse($posicionSegundo);
        $this->assertLessThan($posicionSegundo, $posicionPrimero);
    }

    public function test_listado_del_panel_respeta_orden(): void
    {
        $response = $this->actingAs($this->funcionario)->get('/panel/profesionales');

        $response->assertOk();
        $posicionPrimero = strpos($response->getContent(), 'Bernardo Zapata');
        $posicionSegundo = strpos($response->getContent(), 'Alberto Alvarez');

        $this->assertNotFalse($posicionPrimero);
        $this->assertNotFalse($posicionSegundo);
        $this->assertLessThan($posicionSegundo, $posicionPrimero);
    }

    public function test_portada_respeta_orden(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $posicionPrimero = strpos($response->getContent(), 'Bernardo Zapata');
        $posicionSegundo = strpos($response->getContent(), 'Alberto Alvarez');

        $this->assertNotFalse($posicionPrimero);
        $this->assertNotFalse($posicionSegundo);
        $this->assertLessThan($posicionSegundo, $posicionPrimero);
    }

    public function test_catalogo_servicios_respeta_orden_en_relacion(): void
    {
        $response = $this->get('/servicios');

        $response->assertOk();
        $posicionPrimero = strpos($response->getContent(), 'Bernardo Zapata');
        $posicionSegundo = strpos($response->getContent(), 'Alberto Alvarez');

        $this->assertNotFalse($posicionPrimero);
        $this->assertNotFalse($posicionSegundo);
        $this->assertLessThan($posicionSegundo, $posicionPrimero);
    }
}
