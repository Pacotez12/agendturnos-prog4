<?php

namespace Tests\Feature\Api;

use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfesionalesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_profesionales_en_orden(): void
    {
        $u1 = User::factory()->profesional()->create(['email' => 'pro2@clinica.test']);
        $u2 = User::factory()->profesional()->create(['email' => 'pro1@clinica.test']);

        $p2 = Profesional::factory()->create([
            'user_id' => $u1->id,
            'nombre' => 'Bernardo',
            'apellido' => 'Zapata',
            'orden' => 2,
        ]);
        $p1 = Profesional::factory()->create([
            'user_id' => $u2->id,
            'nombre' => 'Ana',
            'apellido' => 'García',
            'orden' => 1,
        ]);

        $response = $this->getJson('/api/v1/profesionales');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertSame($p1->id, $data[0]['id']);
        $this->assertSame('Ana García', $data[0]['nombre_completo']);
        $this->assertSame($p2->id, $data[1]['id']);
        $this->assertSame('Bernardo Zapata', $data[1]['nombre_completo']);
    }

    public function test_profesional_resource_campos(): void
    {
        $user = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Carlos',
            'apellido' => 'Benítez',
            'orden' => 1,
        ]);

        $esp = Especialidad::factory()->create(['nombre' => 'Endodoncia', 'activa' => true]);
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Tratamiento de conducto',
            'activo' => true,
        ]);
        $profesional->servicios()->attach($servicio->id);

        $response = $this->getJson('/api/v1/profesionales');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'nombre_completo',
                    'especialidades' => [
                        '*' => ['id', 'nombre'],
                    ],
                    'servicios' => [
                        '*' => ['id', 'nombre'],
                    ],
                ],
            ],
        ]);

        $data = $response->json('data.0');
        $this->assertSame('Carlos Benítez', $data['nombre_completo']);
        $this->assertSame($esp->id, $data['especialidades'][0]['id']);
        $this->assertSame('Endodoncia', $data['especialidades'][0]['nombre']);
        $this->assertSame($servicio->id, $data['servicios'][0]['id']);
        $this->assertSame('Tratamiento de conducto', $data['servicios'][0]['nombre']);
    }

    public function test_no_filtra_telefono_ni_email_de_profesionales(): void
    {
        $user = User::factory()->profesional()->create(['email' => 'secreto@clinica.test']);
        Profesional::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Dra. Secreta',
            'apellido' => 'Privada',
        ]);

        $response = $this->getJson('/api/v1/profesionales');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('secreto@clinica.test', $content);
        $this->assertStringNotContainsString('email', $content);
        $this->assertStringNotContainsString('telefono', $content);
    }
}
