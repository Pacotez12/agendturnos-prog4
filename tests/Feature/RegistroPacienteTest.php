<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistroPacienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_registrarse_exitosamente()
    {
        $response = $this->post('/registro', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '1234567',
            'fecha_nacimiento' => '1990-05-15',
            'telefono' => '0981123456',
            'email' => 'juan.perez@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@example.com',
            'rol' => 'paciente',
        ]);

        $this->assertDatabaseHas('pacientes', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '1234567',
        ]);
    }

    public function test_correo_electronico_ya_registrado_falla()
    {
        User::factory()->create([
            'email' => 'existente@example.com',
        ]);

        $response = $this->post('/registro', [
            'nombre' => 'María',
            'apellido' => 'Gómez',
            'tipo_documento' => 'CI',
            'documento' => '7654321',
            'fecha_nacimiento' => '1995-10-20',
            'email' => 'existente@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_documento_repetido_falla_y_no_crea_usuario()
    {
        $userExistente = User::factory()->create();
        Paciente::create([
            'user_id' => $userExistente->id,
            'nombre' => 'Carlos',
            'apellido' => 'Ruiz',
            'tipo_documento' => 'CI',
            'documento' => '11223344',
            'fecha_nacimiento' => '1985-01-01',
        ]);

        $response = $this->post('/registro', [
            'nombre' => 'Pedro',
            'apellido' => 'Benítez',
            'tipo_documento' => 'CI',
            'documento' => '11223344',
            'fecha_nacimiento' => '1992-03-10',
            'email' => 'pedro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('documento');
        $this->assertDatabaseMissing('users', [
            'email' => 'pedro@example.com',
        ]);
    }

    public function test_contrasena_queda_hasheada()
    {
        $this->post('/registro', [
            'nombre' => 'Ana',
            'apellido' => 'Giménez',
            'tipo_documento' => 'CI',
            'documento' => '99887766',
            'fecha_nacimiento' => '1998-07-12',
            'email' => 'ana@example.com',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ]);

        $user = User::where('email', 'ana@example.com')->first();
        
        $this->assertNotNull($user);
        $this->assertFalse($user->password === 'secreta123');
        $this->assertTrue(Hash::check('secreta123', $user->password));
    }

    public function test_confirmacion_contrasena_no_coincide_falla()
    {
        $response = $this->post('/registro', [
            'nombre' => 'Laura',
            'apellido' => 'Duarte',
            'tipo_documento' => 'CI',
            'documento' => '55443322',
            'fecha_nacimiento' => '1993-04-05',
            'email' => 'laura@example.com',
            'password' => 'password123',
            'password_confirmation' => 'passwordDiferente',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_fecha_nacimiento_futura_falla()
    {
        $response = $this->post('/registro', [
            'nombre' => 'Bebé',
            'apellido' => 'Futuro',
            'tipo_documento' => 'CI',
            'documento' => '66778899',
            'fecha_nacimiento' => '2030-01-01',
            'email' => 'bebe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('fecha_nacimiento');
    }

    public function test_usuario_logueado_es_redirigido_si_entra_a_registro()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/registro');

        $response->assertRedirect();
    }
}
