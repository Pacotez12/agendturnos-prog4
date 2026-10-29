<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_sin_sesion_en_ruta_protegida_redirige_a_login(): void
    {
        $response = $this->get('/panel');

        $response->assertRedirect(route('login'));
    }

    public function test_paciente_en_panel_recibe_403(): void
    {
        $paciente = User::factory()->paciente()->create();

        $response = $this->actingAs($paciente)->get('/panel');

        $response->assertForbidden();
    }

    public function test_profesional_en_inicio_paciente_recibe_403(): void
    {
        $profesional = User::factory()->profesional()->create();

        $response = $this->actingAs($profesional)->get('/inicio');

        $response->assertForbidden();
    }

    public function test_funcionario_en_panel_recibe_200(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->get('/panel');

        $response->assertOk();
    }

    public function test_profesional_en_panel_recibe_200(): void
    {
        $profesional = User::factory()->profesional()->create();
        Profesional::factory()->create(['user_id' => $profesional->id]);

        $response = $this->actingAs($profesional)->get('/panel');

        $response->assertOk();
    }

    public function test_profesional_sin_perfil_en_panel_recibe_403(): void
    {
        $profesionalSinPerfil = User::factory()->profesional()->create();

        $this->actingAs($profesionalSinPerfil)->get('/panel')->assertForbidden();
        $this->actingAs($profesionalSinPerfil)->get('/panel/agenda')->assertForbidden();
        $this->actingAs($profesionalSinPerfil)->get('/panel/pacientes')->assertForbidden();
    }

    public function test_paciente_en_inicio_recibe_200_con_su_nombre_si_tiene_perfil(): void
    {
        $user = User::factory()->paciente()->create();
        Paciente::factory()->create([
            'user_id' => $user->id,
            'nombre' => 'Lucía',
            'apellido' => 'Acosta',
        ]);

        $response = $this->actingAs($user)->get('/inicio');

        $response->assertOk();
        $response->assertSee('Lucía');
        $response->assertSee('Todavía no tenés turnos');
    }

    public function test_paciente_sin_perfil_en_inicio_no_lanza_error(): void
    {
        $user = User::factory()->paciente()->create();

        $response = $this->actingAs($user)->get('/inicio');

        $response->assertOk();
        $response->assertSee('Todavía no tenés turnos');
    }
}
