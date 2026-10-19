<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_funcionario_accede_a_listado_de_servicios(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create(['nombre' => 'Endodoncia']);
        Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Tratamiento de conducto',
        ]);

        $response = $this->actingAs($funcionario)->get('/panel/servicios');

        $response->assertOk();
        $response->assertSee('Tratamiento de conducto');
        $response->assertSee('Endodoncia');
        $response->assertSee('Nuevo servicio');
    }

    public function test_paciente_y_profesional_reciben_403_en_admin_servicios(): void
    {
        $paciente = User::factory()->paciente()->create();
        $profesional = User::factory()->profesional()->create();

        $this->actingAs($paciente)->get('/panel/servicios')->assertForbidden();
        $this->actingAs($profesional)->get('/panel/servicios')->assertForbidden();
    }

    public function test_invitado_es_redirigido_a_login_desde_admin_servicios(): void
    {
        $this->get('/panel/servicios')->assertRedirect(route('login'));
    }

    public function test_funcionario_crea_servicio_correctamente(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create();

        $response = $this->actingAs($funcionario)->post('/panel/servicios', [
            'nombre' => 'Extracción simple',
            'especialidad_id' => $esp->id,
            'duracion_min' => 45,
            'precio' => 120000,
            'descripcion' => 'Extracción de piezas dentarias sin complicaciones.',
            'activo' => true,
        ]);

        $response->assertRedirect(route('panel.servicios'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('servicios', [
            'nombre' => 'Extracción simple',
            'especialidad_id' => $esp->id,
            'duracion_min' => 45,
            'precio' => 120000,
            'descripcion' => 'Extracción de piezas dentarias sin complicaciones.',
            'activo' => true,
        ]);
    }

    public function test_funcionario_actualiza_servicio_y_puede_desactivarlo(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create();
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Servicio original',
            'activo' => true,
        ]);

        $response = $this->actingAs($funcionario)->put("/panel/servicios/{$servicio->id}", [
            'nombre' => 'Servicio modificado',
            'especialidad_id' => $esp->id,
            'duracion_min' => 60,
            'precio' => null,
            'descripcion' => 'Nueva descripción',
            'activo' => false,
        ]);

        $response->assertRedirect(route('panel.servicios'));
        $this->assertDatabaseHas('servicios', [
            'id' => $servicio->id,
            'nombre' => 'Servicio modificado',
            'activo' => false,
        ]);

        // Verificar que deja de verse en el catálogo público
        $this->get('/servicios')->assertDontSee('Servicio modificado');
    }

    public function test_validaciones_al_crear_servicio(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create();

        // Nombre vacío
        $responseNombreVacio = $this->actingAs($funcionario)->post('/panel/servicios', [
            'nombre' => '',
            'especialidad_id' => $esp->id,
            'duracion_min' => 30,
        ]);
        $responseNombreVacio->assertSessionHasErrors('nombre');

        // Duración menor a 5 min
        $responseDuracionInvalida = $this->actingAs($funcionario)->post('/panel/servicios', [
            'nombre' => 'Test',
            'especialidad_id' => $esp->id,
            'duracion_min' => 0,
        ]);
        $responseDuracionInvalida->assertSessionHasErrors('duracion_min');

        // Precio negativo
        $responsePrecioNegativo = $this->actingAs($funcionario)->post('/panel/servicios', [
            'nombre' => 'Test',
            'especialidad_id' => $esp->id,
            'duracion_min' => 30,
            'precio' => -1,
        ]);
        $responsePrecioNegativo->assertSessionHasErrors('precio');
    }
}
