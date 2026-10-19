<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEspecialidadesTest extends TestCase
{
    use RefreshDatabase;

    public function test_funcionario_accede_a_listado_de_especialidades(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create(['nombre' => 'Ortodoncia', 'activa' => true]);
        Servicio::factory()->count(3)->create(['especialidad_id' => $esp->id]);

        $response = $this->actingAs($funcionario)->get(route('panel.especialidades'));

        $response->assertOk();
        $response->assertSee('Ortodoncia');
        $response->assertSee('3'); // withCount de servicios
        $response->assertSee('Activa');
        $response->assertSee('Nueva especialidad');
    }

    public function test_paciente_y_profesional_reciben_403_en_admin_especialidades(): void
    {
        $paciente = User::factory()->paciente()->create();
        $profesional = User::factory()->profesional()->create();
        $esp = Especialidad::factory()->create();

        // Index
        $this->actingAs($paciente)->get(route('panel.especialidades'))->assertForbidden();
        $this->actingAs($profesional)->get(route('panel.especialidades'))->assertForbidden();

        // Create
        $this->actingAs($paciente)->get(route('panel.especialidades.create'))->assertForbidden();
        $this->actingAs($profesional)->get(route('panel.especialidades.create'))->assertForbidden();

        // Edit
        $this->actingAs($paciente)->get(route('panel.especialidades.edit', $esp))->assertForbidden();
        $this->actingAs($profesional)->get(route('panel.especialidades.edit', $esp))->assertForbidden();
    }

    public function test_invitado_es_redirigido_a_login_desde_admin_especialidades(): void
    {
        $this->get(route('panel.especialidades'))->assertRedirect(route('login'));
    }

    public function test_funcionario_crea_especialidad_correctamente(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->post(route('panel.especialidades.store'), [
            'nombre' => 'Periodoncia',
            'activa' => true,
        ]);

        $response->assertRedirect(route('panel.especialidades'));
        $response->assertSessionHas('success', 'Especialidad guardada.');

        $this->assertDatabaseHas('especialidades', [
            'nombre' => 'Periodoncia',
            'activa' => true,
        ]);
    }

    public function test_funcionario_actualiza_especialidad(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create(['nombre' => 'Odontopediatria', 'activa' => true]);

        $response = $this->actingAs($funcionario)->put(route('panel.especialidades.update', $esp), [
            'nombre' => 'Odontopediatría Integral',
            'activa' => false,
        ]);

        $response->assertRedirect(route('panel.especialidades'));
        $response->assertSessionHas('success', 'Especialidad guardada.');

        $this->assertDatabaseHas('especialidades', [
            'id' => $esp->id,
            'nombre' => 'Odontopediatría Integral',
            'activa' => false,
        ]);
    }

    public function test_nombre_duplicado_falla_validacion(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        Especialidad::factory()->create(['nombre' => 'Endodoncia']);

        $response = $this->actingAs($funcionario)->post(route('panel.especialidades.store'), [
            'nombre' => 'Endodoncia',
            'activa' => true,
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_editar_especialidad_con_mismo_nombre_no_falla_validacion(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create(['nombre' => 'Endodoncia', 'activa' => true]);

        $response = $this->actingAs($funcionario)->put(route('panel.especialidades.update', $esp), [
            'nombre' => 'Endodoncia',
            'activa' => false,
        ]);

        $response->assertRedirect(route('panel.especialidades'));
        $this->assertDatabaseHas('especialidades', [
            'id' => $esp->id,
            'nombre' => 'Endodoncia',
            'activa' => false,
        ]);
    }

    public function test_desactivar_especialidad_hace_que_sus_servicios_no_aparezcan_en_catalogo(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $esp = Especialidad::factory()->create(['nombre' => 'Cirugía Maxilofacial', 'activa' => true]);
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Cirugía de cordales',
            'activo' => true,
        ]);

        // Visible inicialmente
        $this->get(route('servicios.catalogo'))->assertSee('Cirugía de cordales');

        // Desactivamos la especialidad
        $this->actingAs($funcionario)->put(route('panel.especialidades.update', $esp), [
            'nombre' => 'Cirugía Maxilofacial',
            'activa' => false,
        ]);

        // Ya no aparece en el catálogo
        $this->get(route('servicios.catalogo'))->assertDontSee('Cirugía de cordales');
    }
}
