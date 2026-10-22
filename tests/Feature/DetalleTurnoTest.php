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

class DetalleTurnoTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private Profesional $profesional;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->funcionario = User::factory()->funcionario()->create();

        $profUser = User::factory()->profesional()->create();
        $this->profesional = Profesional::factory()->create([
            'user_id' => $profUser->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($this->servicio->id);
    }

    private function crearTurnoParaPaciente(Paciente $paciente): Turno
    {
        return Turno::create([
            'paciente_id' => $paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => '2026-10-23 09:00:00',
            'fin' => '2026-10-23 09:30:00',
            'estado' => EstadoTurno::Confirmado,
        ]);
    }

    public function test_paciente_con_cuenta_muestra_su_correo(): void
    {
        $usuario = User::factory()->paciente()->create(['email' => 'carlos@paciente.test']);
        $paciente = Paciente::factory()->create([
            'user_id' => $usuario->id,
            'responsable_id' => null,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $turno = $this->crearTurnoParaPaciente($paciente);

        $response = $this->actingAs($this->funcionario)->get(route('panel.turnos.show', $turno));

        $response->assertOk();
        $response->assertSee('Correo');
        $response->assertSee('carlos@paciente.test');
        $response->assertDontSee('(responsable)');
        $response->assertDontSee('Responsable');
    }

    public function test_familiar_sin_cuenta_muestra_correo_del_responsable_y_su_nombre_como_responsable(): void
    {
        $responsableUser = User::factory()->paciente()->create(['email' => 'mama@familia.test']);
        Paciente::factory()->create([
            'user_id' => $responsableUser->id,
            'nombre' => 'Laura',
            'apellido' => 'Martínez',
        ]);

        $hijo = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $responsableUser->id,
            'nombre' => 'Mateo',
            'apellido' => 'Martínez',
        ]);

        $turno = $this->crearTurnoParaPaciente($hijo);

        $response = $this->actingAs($this->funcionario)->get(route('panel.turnos.show', $turno));

        $response->assertOk();
        $response->assertSee('Correo');
        $response->assertSee('mama@familia.test');
        $response->assertSee('(responsable)');
        $response->assertSee('Responsable');
        $response->assertSee('Laura Martínez');
    }

    public function test_paciente_sin_cuenta_ni_responsable_muestra_guion(): void
    {
        $paciente = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => null,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
        ]);

        $turno = $this->crearTurnoParaPaciente($paciente);

        $response = $this->actingAs($this->funcionario)->get(route('panel.turnos.show', $turno));

        $response->assertOk();
        $response->assertSee('Correo');
        $response->assertDontSee('(responsable)');
        $response->assertDontSee('Responsable');
    }
}
