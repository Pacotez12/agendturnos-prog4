<?php

namespace Tests\Feature\Api;

use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisponibilidadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_disponibilidad_valida_campos_requeridos(): void
    {
        $response = $this->getJson('/api/v1/disponibilidad');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profesional_id', 'servicio_id', 'fecha']);
    }

    public function test_disponibilidad_rechaza_fecha_pasada(): void
    {
        $user = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $user->id]);
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);
        $profesional->servicios()->attach($servicio->id);

        $ayer = Carbon::yesterday('America/Asuncion')->toDateString();

        $response = $this->getJson("/api/v1/disponibilidad?profesional_id={$profesional->id}&servicio_id={$servicio->id}&fecha={$ayer}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fecha']);
    }

    public function test_disponibilidad_rechaza_si_profesional_no_ofrece_el_servicio(): void
    {
        $user = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $user->id]);
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create(['especialidad_id' => $esp->id, 'activo' => true]);
        // No attach

        $fecha = Carbon::tomorrow('America/Asuncion')->toDateString();

        $response = $this->getJson("/api/v1/disponibilidad?profesional_id={$profesional->id}&servicio_id={$servicio->id}&fecha={$fecha}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['servicio_id']);
    }

    public function test_disponibilidad_rechaza_servicio_con_especialidad_inactiva(): void
    {
        $user = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $user->id]);
        $espInactiva = Especialidad::factory()->create(['activa' => false]);
        $servicio = Servicio::factory()->create(['especialidad_id' => $espInactiva->id, 'activo' => true]);
        $profesional->servicios()->attach($servicio->id);

        $fecha = Carbon::tomorrow('America/Asuncion')->toDateString();

        $response = $this->getJson("/api/v1/disponibilidad?profesional_id={$profesional->id}&servicio_id={$servicio->id}&fecha={$fecha}");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['servicio_id']);
    }

    public function test_disponibilidad_devuelve_franjas_libres_en_iso_8601(): void
    {
        $user = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $user->id]);
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $profesional->servicios()->attach($servicio->id);

        $consultorio = Consultorio::factory()->create(['activo' => true]);

        // Buscamos un día futuro que coincida con el dia_semana de la disponibilidad
        $fechaFutura = Carbon::now('America/Asuncion')->addDays(2);
        $diaSemana = $fechaFutura->dayOfWeekIso;

        Disponibilidad::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '09:00',
            'hora_fin' => '10:00',
        ]);

        $fechaStr = $fechaFutura->toDateString();
        $response = $this->getJson("/api/v1/disponibilidad?profesional_id={$profesional->id}&servicio_id={$servicio->id}&fecha={$fechaStr}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'inicio',
                    'fin',
                ],
            ],
        ]);

        $franjas = $response->json('data');
        $this->assertNotEmpty($franjas);

        // Validar formato ISO 8601 con offset o Z
        $primerInicio = $franjas[0]['inicio'];
        $primerFin = $franjas[0]['fin'];

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $primerInicio);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $primerFin);
        $this->assertStringContainsString('09:00:00', $primerInicio);
        $this->assertStringContainsString('09:30:00', $primerFin);
    }
}
