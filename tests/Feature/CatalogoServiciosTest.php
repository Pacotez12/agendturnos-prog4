<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoServiciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogo_responde_ok_sin_sesion(): void
    {
        $response = $this->get('/servicios');

        $response->assertOk();
        $response->assertSee('Nuestros servicios');
        $response->assertSee('Elegí el tratamiento y reservá tu turno.');
    }

    public function test_catalogo_muestra_servicio_activo_y_no_inactivo(): void
    {
        $esp = Especialidad::factory()->create(['nombre' => 'Odontología general', 'activa' => true]);
        $activo = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Limpieza profunda',
            'activo' => true,
        ]);
        $inactivo = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Tratamiento experimental',
            'activo' => false,
        ]);

        $response = $this->get('/servicios');

        $response->assertOk();
        $response->assertSee('Limpieza profunda');
        $response->assertDontSee('Tratamiento experimental');
    }

    public function test_catalogo_muestra_precio_formateado_o_consultar_precio(): void
    {
        $esp = Especialidad::factory()->create(['activa' => true]);
        Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Consulta con precio',
            'precio' => 150000,
            'activo' => true,
        ]);
        Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Consulta sin precio',
            'precio' => null,
            'activo' => true,
        ]);

        $response = $this->get('/servicios');

        $response->assertOk();
        $response->assertSee('Gs. 150.000');
        $response->assertSee('Consultar precio');
    }

    public function test_filtro_por_especialidad(): void
    {
        $esp1 = Especialidad::factory()->create(['nombre' => 'Ortodoncia', 'activa' => true]);
        $esp2 = Especialidad::factory()->create(['nombre' => 'Endodoncia', 'activa' => true]);

        $serv1 = Servicio::factory()->create([
            'especialidad_id' => $esp1->id,
            'nombre' => 'Brackets metálicos',
            'activo' => true,
        ]);
        $serv2 = Servicio::factory()->create([
            'especialidad_id' => $esp2->id,
            'nombre' => 'Conducto molar',
            'activo' => true,
        ]);

        $response = $this->get('/servicios?especialidad='.$esp1->id);

        $response->assertOk();
        $response->assertSee('Brackets metálicos');
        $response->assertDontSee('Conducto molar');
    }

    public function test_muestra_profesionales_que_ofrecen_el_servicio(): void
    {
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Limpieza dental',
            'activo' => true,
        ]);

        $profesional = Profesional::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);
        $servicio->profesionales()->attach($profesional->id);

        $response = $this->get('/servicios');

        $response->assertOk();
        $response->assertSee('Ana González');
    }

    public function test_boton_reservar_redirige_a_login_si_es_invitado_o_a_inicio_si_es_paciente(): void
    {
        $esp = Especialidad::factory()->create(['activa' => true]);
        Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Consulta general',
            'activo' => true,
        ]);

        // Como invitado
        $responseInvitado = $this->get('/servicios');
        $responseInvitado->assertSee(route('login'));

        // Como paciente
        $paciente = User::factory()->paciente()->create();
        $responsePaciente = $this->actingAs($paciente)->get('/servicios');
        $responsePaciente->assertSee(route('paciente.inicio'));
    }
}
