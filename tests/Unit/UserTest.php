<?php

namespace Tests\Unit;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_nombre_visible_paciente(): void
    {
        $user = User::factory()->paciente()->create();
        Paciente::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $this->assertSame('Carlos Gómez', $user->nombreVisible());
    }

    public function test_nombre_visible_profesional(): void
    {
        $user = User::factory()->profesional()->create();
        Profesional::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->assertSame('Ana González', $user->nombreVisible());
    }

    public function test_nombre_visible_funcionario(): void
    {
        $user = User::factory()->funcionario()->create();

        $this->assertSame('Recepción', $user->nombreVisible());
    }

    public function test_nombre_visible_sin_relacion_devuelve_sistema(): void
    {
        // Usuario sin rol ni paciente ni profesional (o rol no funcionario sin perfil)
        $user = new User(['rol' => RolUsuario::Paciente]);

        $this->assertSame('Sistema', $user->nombreVisible());
    }
}
