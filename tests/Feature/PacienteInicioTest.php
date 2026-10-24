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

class PacienteInicioTest extends TestCase
{
    use RefreshDatabase;

    private User $pacienteUser;

    private Paciente $paciente;

    private Profesional $profesional;

    private Servicio $servicio;

    private Consultorio $consultorio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-23 10:00:00', config('app.timezone')));

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'carlos@paciente.test']);
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $profUser = User::factory()->profesional()->create();
        $this->profesional = Profesional::factory()->create([
            'user_id' => $profUser->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $esp = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Limpieza dental',
            'duracion_min' => 30,
            'activo' => true,
        ]);

        $this->profesional->servicios()->attach($this->servicio->id);
    }

    private function crearTurno(string $inicio, string $fin, EstadoTurno $estado = EstadoTurno::Confirmado, ?Paciente $pac = null): Turno
    {
        return Turno::create([
            'paciente_id' => $pac ? $pac->id : $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse($inicio, config('app.timezone')),
            'fin' => Carbon::parse($fin, config('app.timezone')),
            'estado' => $estado,
        ]);
    }

    public function test_sin_turnos_proximos_muestra_estado_vacio_y_boton_reservar(): void
    {
        $response = $this->actingAs($this->pacienteUser)->get('/inicio');

        $response->assertOk();
        $response->assertSee('Hola, Carlos');
        $response->assertSee('No tenés turnos próximos');
        $response->assertSee(route('paciente.reservar'));
        $response->assertDontSee('Pronto vas a poder reservar desde acá');
    }

    public function test_proximo_turno_del_paciente_muestra_tarjeta_destacada(): void
    {
        // Turno pasado (no debe salir)
        $this->crearTurno('2026-10-22 09:00:00', '2026-10-22 09:30:00', EstadoTurno::Atendido);
        // Turno cancelado futuro (no debe salir)
        $this->crearTurno('2026-10-24 09:00:00', '2026-10-24 09:30:00', EstadoTurno::Cancelado);
        // Turno confirmado próximo más cercano
        $turno1 = $this->crearTurno('2026-10-24 11:00:00', '2026-10-24 11:30:00', EstadoTurno::Confirmado);
        // Turno posterior
        $this->crearTurno('2026-10-28 11:00:00', '2026-10-28 11:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->pacienteUser)->get('/inicio');

        $response->assertOk();
        $response->assertSee('Tu próximo turno');
        $response->assertSee('Limpieza dental');
        $response->assertSee('Ana González');
        $response->assertSee('11:00');
        $response->assertSee(route('paciente.turnos.show', $turno1));
        $response->assertSee(route('paciente.reservar'));
        $response->assertSee(route('paciente.turnos'));
    }

    public function test_muestra_tarjetas_de_proximos_turnos_de_familiares(): void
    {
        // Familiar a cargo
        $hijo = Paciente::factory()->create([
            'nombre' => 'Lucas',
            'apellido' => 'Gómez',
            'responsable_id' => $this->pacienteUser->id,
        ]);

        $turnoHijo = $this->crearTurno('2026-10-25 15:00:00', '2026-10-25 15:30:00', EstadoTurno::Solicitado, $hijo);

        $response = $this->actingAs($this->pacienteUser)->get('/inicio');

        $response->assertOk();
        $response->assertSee('Turno de Lucas');
        $response->assertSee('15:00');
        $response->assertSee(route('paciente.turnos.show', $turnoHijo));
    }

    public function test_inicio_del_paciente_muestra_fecha_con_de_en_minuscula_y_sin_clase_capitalize(): void
    {
        // Turno el 26 de octubre de 2026 a las 10:00 (lunes)
        $this->crearTurno('2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);

        $response = $this->actingAs($this->pacienteUser)->get('/inicio');

        $response->assertOk();
        $response->assertSee('26 de octubre');
        $response->assertDontSee('font-display text-ink-900 capitalize', false);
    }
}
