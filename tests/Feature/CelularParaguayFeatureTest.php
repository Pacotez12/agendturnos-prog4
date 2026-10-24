<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CelularParaguayFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_guarda_telefono_normalizado(): void
    {
        $response = $this->post('/registro', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '1234567',
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '0981 123 456',
            'email' => 'juan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('paciente.inicio'));

        $this->assertDatabaseHas('pacientes', [
            'documento' => '1234567',
            'telefono' => '0981123456',
        ]);
    }

    public function test_registro_rechaza_telefono_invalido(): void
    {
        $response = $this->post('/registro', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '1234568',
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '021 123 456',
            'email' => 'juan2@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors([
            'telefono' => 'Ingresá un celular válido, por ejemplo 0981 123 456.',
        ]);
    }

    public function test_alta_desde_panel_guarda_telefono_normalizado(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->post('/panel/pacientes', [
            'nombre' => 'Carlos',
            'apellido' => 'Benítez',
            'tipo_documento' => 'CI',
            'documento' => '7654321',
            'fecha_nacimiento' => '1985-05-15',
            'telefono' => '+595 971 123456',
        ]);

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pacientes', [
            'documento' => '7654321',
            'telefono' => '0971123456',
        ]);
    }

    public function test_alta_desde_panel_rechaza_telefono_invalido(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->post('/panel/pacientes', [
            'nombre' => 'Carlos',
            'apellido' => 'Benítez',
            'tipo_documento' => 'CI',
            'documento' => '7654322',
            'fecha_nacimiento' => '1985-05-15',
            'telefono' => '0981 12345', // Corto
        ]);

        $response->assertSessionHasErrors([
            'telefono' => 'Ingresá un celular válido, por ejemplo 0981 123 456.',
        ]);
    }
}
