<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosPorProfesionalTest extends TestCase
{
    use RefreshDatabase;

    public function test_funcionario_accede_a_listado_de_profesionales(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'González',
            'matricula' => 'PY-1001',
        ]);
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicios = Servicio::factory()->count(2)->create(['especialidad_id' => $esp->id, 'activo' => true]);
        $profesional->servicios()->attach($servicios->pluck('id'));

        $response = $this->actingAs($funcionario)->get(route('panel.profesionales'));

        $response->assertOk();
        $response->assertSee('Ana González');
        $response->assertSee('PY-1001');
        $response->assertSee('2'); // cantidad de servicios
        $response->assertSee('Servicios que ofrece');
        $response->assertSee(route('panel.profesionales.servicios', $profesional));
    }

    public function test_paciente_y_profesional_reciben_403_en_pantallas_de_profesionales(): void
    {
        $paciente = User::factory()->paciente()->create();
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        // Listado
        $this->actingAs($paciente)->get(route('panel.profesionales'))->assertForbidden();
        $this->actingAs($profesionalUser)->get(route('panel.profesionales'))->assertForbidden();

        // Pantalla de servicios que ofrece
        $this->actingAs($paciente)->get(route('panel.profesionales.servicios', $profesional))->assertForbidden();
        $this->actingAs($profesionalUser)->get(route('panel.profesionales.servicios', $profesional))->assertForbidden();

        // Actualizar servicios
        $this->actingAs($paciente)->put(route('panel.profesionales.servicios.update', $profesional), ['servicios' => []])->assertForbidden();
        $this->actingAs($profesionalUser)->put(route('panel.profesionales.servicios.update', $profesional), ['servicios' => []])->assertForbidden();
    }

    public function test_invitado_es_redirigido_a_login_desde_profesionales(): void
    {
        $profesional = Profesional::factory()->create();

        $this->get(route('panel.profesionales'))->assertRedirect(route('login'));
        $this->get(route('panel.profesionales.servicios', $profesional))->assertRedirect(route('login'));
    }

    public function test_asignar_servicios_a_una_profesional(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create();
        $esp = Especialidad::factory()->create(['activa' => true]);
        $s1 = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);
        $s2 = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);

        $response = $this->actingAs($funcionario)->put(route('panel.profesionales.servicios.update', $profesional), [
            'servicios' => [$s1->id, $s2->id],
        ]);

        $response->assertRedirect(route('panel.profesionales.servicios', $profesional));
        $response->assertSessionHas('success', 'Servicios actualizados.');

        $this->assertDatabaseHas('profesional_servicio', [
            'profesional_id' => $profesional->id,
            'servicio_id' => $s1->id,
        ]);
        $this->assertDatabaseHas('profesional_servicio', [
            'profesional_id' => $profesional->id,
            'servicio_id' => $s2->id,
        ]);
    }

    public function test_quitar_un_servicio_sin_turnos_lo_saca(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create();
        $esp = Especialidad::factory()->create(['activa' => true]);
        $s1 = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);
        $s2 = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);

        $profesional->servicios()->attach([$s1->id, $s2->id]);

        // Quitamos s1 enviando solo s2
        $response = $this->actingAs($funcionario)->put(route('panel.profesionales.servicios.update', $profesional), [
            'servicios' => [$s2->id],
        ]);

        $response->assertRedirect(route('panel.profesionales.servicios', $profesional));
        $response->assertSessionHas('success', 'Servicios actualizados.');

        $this->assertDatabaseMissing('profesional_servicio', [
            'profesional_id' => $profesional->id,
            'servicio_id' => $s1->id,
        ]);
        $this->assertDatabaseHas('profesional_servicio', [
            'profesional_id' => $profesional->id,
            'servicio_id' => $s2->id,
        ]);
    }

    public function test_guardar_conserva_servicios_inactivos_que_no_se_muestran(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create();
        $esp = Especialidad::factory()->create(['activa' => true]);
        $activo = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);
        $inactivo = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => false]);
        $espInactiva = Especialidad::factory()->create(['activa' => false]);
        $deEspInactiva = Servicio::factory()->create(['especialidad_id' => $espInactiva->id, 'activo' => true]);

        $profesional->servicios()->attach([$activo->id, $inactivo->id, $deEspInactiva->id]);

        $this->actingAs($funcionario)->put(route('panel.profesionales.servicios.update', $profesional), [
            'servicios' => [$activo->id],
        ]);

        $this->assertDatabaseHas('profesional_servicio', ['profesional_id' => $profesional->id, 'servicio_id' => $inactivo->id]);
        $this->assertDatabaseHas('profesional_servicio', ['profesional_id' => $profesional->id, 'servicio_id' => $deEspInactiva->id]);
    }

    public function test_intentar_quitar_un_servicio_con_turnos_lo_conserva_y_la_pantalla_muestra_aviso(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create();
        $consultorio = Consultorio::factory()->create(['activo' => true]);
        $paciente = Paciente::factory()->create();

        $esp = Especialidad::factory()->create(['activa' => true]);
        $sConTurno = Servicio::factory()->create(['especialidad_id' => $esp->id, 'nombre' => 'Limpieza con turno', 'activo' => true]);
        $sSinTurno = Servicio::factory()->create(['especialidad_id' => $esp->id, 'nombre' => 'Blanqueamiento', 'activo' => true]);

        $profesional->servicios()->attach([$sConTurno->id, $sSinTurno->id]);

        // Creamos un turno para sConTurno
        Turno::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
            'paciente_id' => $paciente->id,
            'servicio_id' => $sConTurno->id,
            'inicio' => Carbon::now()->addDays(2),
            'fin' => Carbon::now()->addDays(2)->addMinutes(45),
            'estado' => EstadoTurno::Confirmado,
        ]);

        // Pantalla muestra "Tiene turnos registrados"
        $resVista = $this->actingAs($funcionario)->get(route('panel.profesionales.servicios', $profesional));
        $resVista->assertOk();
        $resVista->assertSee('Tiene turnos registrados');
        $resVista->assertSee('Limpieza con turno');

        // Intentamos guardar sin sConTurno (enviando arreglo vacío o solo sSinTurno)
        $response = $this->actingAs($funcionario)->put(route('panel.profesionales.servicios.update', $profesional), [
            'servicios' => [],
        ]);

        $response->assertRedirect(route('panel.profesionales.servicios', $profesional));
        $response->assertSessionHas('success', 'Servicios actualizados.');

        // Se debió conservar sConTurno porque tiene turnos registrados
        $this->assertDatabaseHas('profesional_servicio', [
            'profesional_id' => $profesional->id,
            'servicio_id' => $sConTurno->id,
        ]);
        // Y el que no tenía turnos sí se quitó
        $this->assertDatabaseMissing('profesional_servicio', [
            'profesional_id' => $profesional->id,
            'servicio_id' => $sSinTurno->id,
        ]);
    }
}
