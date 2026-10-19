<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InicioPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_inicio_responde_ok_sin_sesion(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Tu sonrisa, en buenas manos');
        $response->assertSee('Félix López 407 c/ Fermín López, San Pablo, Asunción');
        $response->assertSee(route('servicios.catalogo'));
        $response->assertDontSee('Laravel');
    }

    public function test_inicio_muestra_profesionales_de_la_base_de_datos(): void
    {
        $especialidad = Especialidad::factory()->create(['nombre' => 'Odontopediatría']);
        $servicio = Servicio::factory()->create(['especialidad_id' => $especialidad->id]);

        $profesional = Profesional::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'González',
            'matricula' => '1234',
        ]);
        $profesional->servicios()->attach($servicio);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Ana');
        $response->assertSee('González');
        $response->assertSee('Odontopediatría');
        $response->assertSee('Reg. Prof. 1234');
    }

    public function test_usuario_no_autenticado_ve_botones_de_reserva_hacia_registro(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('registro'));
    }

    public function test_paciente_autenticado_ve_boton_hacia_su_inicio(): void
    {
        $paciente = User::factory()->paciente()->create();

        $response = $this->actingAs($paciente)->get('/');

        $response->assertOk();
        $response->assertSee(route('paciente.inicio'));
    }

    public function test_inicio_incluye_meta_descripcion(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
    }
}
