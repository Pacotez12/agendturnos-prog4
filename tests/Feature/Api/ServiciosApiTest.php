<?php

namespace Tests\Feature\Api;

use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_servicios_activos(): void
    {
        $esp = Especialidad::factory()->create(['nombre' => 'Ortodoncia', 'activa' => true]);
        $user = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Ana',
            'apellido' => 'García',
        ]);

        $servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Brackets estéticos',
            'descripcion' => 'Tratamiento correctivo',
            'duracion_min' => 45,
            'precio' => 350000,
            'activo' => true,
        ]);
        $servicio->profesionales()->attach($profesional->id);

        $response = $this->getJson('/api/v1/servicios');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'nombre',
                    'descripcion',
                    'duracion_min',
                    'precio',
                    'especialidad' => [
                        'id',
                        'nombre',
                    ],
                    'profesionales_ids',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($servicio->id, $data[0]['id']);
        $this->assertSame('Brackets estéticos', $data[0]['nombre']);
        $this->assertSame('Tratamiento correctivo', $data[0]['descripcion']);
        $this->assertSame(45, $data[0]['duracion_min']);
        $this->assertSame(350000, $data[0]['precio']);
        $this->assertSame($esp->id, $data[0]['especialidad']['id']);
        $this->assertSame('Ortodoncia', $data[0]['especialidad']['nombre']);
        $this->assertEquals([$profesional->id], $data[0]['profesionales_ids']);
    }

    public function test_servicio_inactivo_no_aparece(): void
    {
        $esp = Especialidad::factory()->create(['activa' => true]);
        Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Servicio Activo',
            'activo' => true,
        ]);
        Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Servicio Desactivado',
            'activo' => false,
        ]);

        $response = $this->getJson('/api/v1/servicios');

        $response->assertOk();
        $nombres = collect($response->json('data'))->pluck('nombre')->all();
        $this->assertContains('Servicio Activo', $nombres);
        $this->assertNotContains('Servicio Desactivado', $nombres);
    }

    public function test_servicio_con_especialidad_inactiva_no_aparece(): void
    {
        $espActiva = Especialidad::factory()->create(['activa' => true]);
        $espInactiva = Especialidad::factory()->create(['activa' => false]);

        Servicio::factory()->create([
            'especialidad_id' => $espActiva->id,
            'nombre' => 'Servicio de Especialidad Activa',
            'activo' => true,
        ]);
        Servicio::factory()->create([
            'especialidad_id' => $espInactiva->id,
            'nombre' => 'Servicio de Especialidad Inactiva',
            'activo' => true,
        ]);

        $response = $this->getJson('/api/v1/servicios');

        $response->assertOk();
        $nombres = collect($response->json('data'))->pluck('nombre')->all();
        $this->assertContains('Servicio de Especialidad Activa', $nombres);
        $this->assertNotContains('Servicio de Especialidad Inactiva', $nombres);
    }
}
