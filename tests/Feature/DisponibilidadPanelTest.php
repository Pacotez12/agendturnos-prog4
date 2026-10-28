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
use Tests\TestCase;

class DisponibilidadPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_profesional_accede_a_su_panel_de_disponibilidad(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create([
            'user_id' => $profesionalUser->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);
        Consultorio::factory()->create(['activo' => true]);

        $response = $this->actingAs($profesionalUser)->get(route('panel.disponibilidad'));

        $response->assertOk();
        $response->assertSee('Ana González');
        $response->assertSee('Horario semanal');
        $response->assertSee('Bloqueos');
        $response->assertSee('Vista previa');
    }

    public function test_paciente_recibe_403_en_panel_de_disponibilidad(): void
    {
        $paciente = User::factory()->paciente()->create();

        $this->actingAs($paciente)->get(route('panel.disponibilidad'))->assertForbidden();
    }

    public function test_profesional_recibe_403_al_intentar_ver_o_editar_otra_profesional(): void
    {
        $user1 = User::factory()->profesional()->create();
        $profesional1 = Profesional::factory()->create(['user_id' => $user1->id]);

        $user2 = User::factory()->profesional()->create();
        $profesional2 = Profesional::factory()->create(['user_id' => $user2->id]);

        $bloqueo2 = Bloqueo::factory()->create(['profesional_id' => $profesional2->id]);

        // Ver disponibilidad de otra
        $this->actingAs($user1)
            ->get(route('panel.disponibilidad', ['profesional_id' => $profesional2->id]))
            ->assertForbidden();

        // Modificar horario de otra
        $this->actingAs($user1)
            ->put(route('panel.disponibilidad.horario'), [
                'profesional_id' => $profesional2->id,
                'franjas' => [
                    ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
                ],
            ])
            ->assertForbidden();

        // Crear bloqueo para otra
        $this->actingAs($user1)
            ->post(route('panel.disponibilidad.bloqueos.store'), [
                'profesional_id' => $profesional2->id,
                'desde' => '2026-11-01 08:00',
                'hasta' => '2026-11-01 12:00',
            ])
            ->assertForbidden();

        // Quitar bloqueo de otra
        $this->actingAs($user1)
            ->delete(route('panel.disponibilidad.bloqueos.destroy', $bloqueo2))
            ->assertForbidden();
    }

    public function test_funcionario_puede_seleccionar_profesional(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        Consultorio::factory()->create(['activo' => true]);

        $profesional1 = Profesional::factory()->create(['nombre' => 'Ana', 'apellido' => 'González']);
        $profesional2 = Profesional::factory()->create(['nombre' => 'Gloria', 'apellido' => 'Godoy']);

        // Por defecto toma a la primera
        $res1 = $this->actingAs($funcionario)->get(route('panel.disponibilidad'));
        $res1->assertOk();
        $res1->assertSee('Ana González');

        // Puede cambiar a la segunda
        $res2 = $this->actingAs($funcionario)->get(route('panel.disponibilidad', ['profesional_id' => $profesional2->id]));
        $res2->assertOk();
        $res2->assertSee('Gloria Godoy');
    }

    public function test_guardar_horario_semanal_reemplaza_franjas(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);
        $consultorio = Consultorio::factory()->create(['activo' => true]);

        // Franja vieja
        Disponibilidad::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
            'dia_semana' => 5,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '14:00:00',
        ]);

        $response = $this->actingAs($profesionalUser)->put(route('panel.disponibilidad.horario'), [
            'franjas' => [
                ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
                ['dia_semana' => 1, 'hora_inicio' => '14:00', 'hora_fin' => '18:00'],
                ['dia_semana' => 2, 'hora_inicio' => '09:00', 'hora_fin' => '13:00'],
            ],
        ]);

        $response->assertRedirect(route('panel.disponibilidad'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('disponibilidad', [
            'profesional_id' => $profesional->id,
            'dia_semana' => 5,
        ]);

        $this->assertDatabaseHas('disponibilidad', [
            'profesional_id' => $profesional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fin' => '12:00:00',
        ]);
        $this->assertDatabaseHas('disponibilidad', [
            'profesional_id' => $profesional->id,
            'dia_semana' => 1,
            'hora_inicio' => '14:00:00',
            'hora_fin' => '18:00:00',
        ]);
        $this->assertDatabaseHas('disponibilidad', [
            'profesional_id' => $profesional->id,
            'dia_semana' => 2,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
        ]);
    }

    public function test_guardar_horario_con_franjas_superpuestas_en_mismo_dia_falla_validacion(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);
        Consultorio::factory()->create(['activo' => true]);

        // Caso 1: Superposición en el mismo día
        $response = $this->actingAs($profesionalUser)->put(route('panel.disponibilidad.horario'), [
            'franjas' => [
                ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
                ['dia_semana' => 1, 'hora_inicio' => '11:00', 'hora_fin' => '15:00'],
            ],
        ]);

        $response->assertSessionHasErrors();

        // Caso 2: Fin anterior o igual a inicio
        $responseFinInvalido = $this->actingAs($profesionalUser)->put(route('panel.disponibilidad.horario'), [
            'franjas' => [
                ['dia_semana' => 2, 'hora_inicio' => '12:00', 'hora_fin' => '10:00'],
            ],
        ]);

        $responseFinInvalido->assertSessionHasErrors();
    }

    public function test_guardar_horario_con_hora_malformada_devuelve_error_de_validacion_no_500(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $response = $this->actingAs($profesionalUser)->put(route('panel.disponibilidad.horario'), [
            'franjas' => [
                [
                    'dia_semana' => 1,
                    'hora_inicio' => '08:00',
                    'hora_fin' => 'hora-invalida',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('franjas.0.hora_fin');
    }

    public function test_guardar_horario_con_franjas_contiguas_pasa_validacion(): void
    {
        Consultorio::factory()->create(['activo' => true]);
        $profesionalUser = User::factory()->profesional()->create();
        Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $response = $this->actingAs($profesionalUser)->put(route('panel.disponibilidad.horario'), [
            'franjas' => [
                ['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '12:00'],
                ['dia_semana' => 1, 'hora_inicio' => '12:00', 'hora_fin' => '16:00'],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('panel.disponibilidad'));
    }

    public function test_crear_bloqueo_sin_turnos_lo_persiste(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);

        $response = $this->actingAs($profesionalUser)->post(route('panel.disponibilidad.bloqueos.store'), [
            'desde' => '2026-11-10 08:00',
            'hasta' => '2026-11-12 18:00',
            'motivo' => 'Vacaciones de noviembre',
        ]);

        $response->assertRedirect(route('panel.disponibilidad'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bloqueos', [
            'profesional_id' => $profesional->id,
            'motivo' => 'Vacaciones de noviembre',
        ]);
    }

    public function test_funcionario_debe_especificar_profesional_al_crear_bloqueo(): void
    {
        $funcionarioUser = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create();

        // Sin profesional_id falla validación
        $responseSinProf = $this->actingAs($funcionarioUser)->post(route('panel.disponibilidad.bloqueos.store'), [
            'desde' => '2026-11-10 08:00',
            'hasta' => '2026-11-12 18:00',
            'motivo' => 'Capacitación',
        ]);
        $responseSinProf->assertSessionHasErrors('profesional_id');

        // Con profesional_id válido pasa y crea el bloqueo
        $responseConProf = $this->actingAs($funcionarioUser)->post(route('panel.disponibilidad.bloqueos.store'), [
            'profesional_id' => $profesional->id,
            'desde' => '2026-11-10 08:00',
            'hasta' => '2026-11-12 18:00',
            'motivo' => 'Capacitación',
        ]);
        $responseConProf->assertRedirect(route('panel.disponibilidad', ['profesional_id' => $profesional->id]));
        $this->assertDatabaseHas('bloqueos', [
            'profesional_id' => $profesional->id,
            'motivo' => 'Capacitación',
        ]);
    }

    public function test_crear_bloqueo_con_turnos_sin_confirmar_falla_con_advertencia_y_no_lo_crea(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);
        $consultorio = Consultorio::factory()->create(['activo' => true]);
        $paciente = Paciente::factory()->create();
        $servicio = Servicio::factory()->create();

        // Turno existente en el rango
        $turno = Turno::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
            'paciente_id' => $paciente->id,
            'servicio_id' => $servicio->id,
            'inicio' => Carbon::parse('2026-11-10 10:00:00'),
            'fin' => Carbon::parse('2026-11-10 10:45:00'),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $response = $this->actingAs($profesionalUser)->post(route('panel.disponibilidad.bloqueos.store'), [
            'desde' => '2026-11-10 08:00',
            'hasta' => '2026-11-10 12:00',
            'motivo' => 'Capacitación',
            // Sin confirmar=1
        ]);

        $response->assertSessionHasErrors(['confirmar']);
        $this->assertDatabaseMissing('bloqueos', [
            'profesional_id' => $profesional->id,
            'motivo' => 'Capacitación',
        ]);

        $this->assertDatabaseHas('turnos', [
            'id' => $turno->id,
            'estado' => EstadoTurno::Confirmado->value,
        ]);
    }

    public function test_crear_bloqueo_con_turnos_con_confirmar_crea_bloqueo_y_no_toca_turnos(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);
        $consultorio = Consultorio::factory()->create(['activo' => true]);
        $paciente = Paciente::factory()->create();
        $servicio = Servicio::factory()->create();

        $turno = Turno::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
            'paciente_id' => $paciente->id,
            'servicio_id' => $servicio->id,
            'inicio' => Carbon::parse('2026-11-10 10:00:00'),
            'fin' => Carbon::parse('2026-11-10 10:45:00'),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $response = $this->actingAs($profesionalUser)->post(route('panel.disponibilidad.bloqueos.store'), [
            'desde' => '2026-11-10 08:00',
            'hasta' => '2026-11-10 12:00',
            'motivo' => 'Capacitación confirmada',
            'confirmar' => 1,
        ]);

        $response->assertRedirect(route('panel.disponibilidad'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bloqueos', [
            'profesional_id' => $profesional->id,
            'motivo' => 'Capacitación confirmada',
        ]);

        // Los turnos quedan intactos
        $this->assertDatabaseHas('turnos', [
            'id' => $turno->id,
            'estado' => EstadoTurno::Confirmado->value,
        ]);
    }

    public function test_quitar_bloqueo_lo_elimina(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $profesionalUser->id]);
        $bloqueo = Bloqueo::factory()->create([
            'profesional_id' => $profesional->id,
            'motivo' => 'Bloqueo a eliminar',
        ]);

        $response = $this->actingAs($profesionalUser)
            ->delete(route('panel.disponibilidad.bloqueos.destroy', $bloqueo));

        $response->assertRedirect(route('panel.disponibilidad'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('bloqueos', [
            'id' => $bloqueo->id,
        ]);
    }

    public function test_profesional_no_puede_quitar_bloqueo_de_otra_profesional(): void
    {
        $profesionalUser = User::factory()->profesional()->create();
        Profesional::factory()->create(['user_id' => $profesionalUser->id]);
        $bloqueoAjeno = Bloqueo::factory()->create([
            'profesional_id' => Profesional::factory()->create()->id,
        ]);

        $this->actingAs($profesionalUser)
            ->delete(route('panel.disponibilidad.bloqueos.destroy', $bloqueoAjeno))
            ->assertForbidden();

        $this->assertDatabaseHas('bloqueos', ['id' => $bloqueoAjeno->id]);
    }
}
