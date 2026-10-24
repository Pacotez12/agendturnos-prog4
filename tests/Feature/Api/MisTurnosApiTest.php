<?php

namespace Tests\Feature\Api;

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

class MisTurnosApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioPaciente;

    private Paciente $pacienteTitular;

    private Profesional $profesional;

    private Servicio $servicio;

    private Consultorio $consultorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuarioPaciente = User::factory()->paciente()->create();
        $this->pacienteTitular = Paciente::factory()->create([
            'user_id' => $this->usuarioPaciente->id,
            'nombre' => 'María',
            'apellido' => 'González',
        ]);

        $userPro = User::factory()->profesional()->create();
        $this->profesional = Profesional::factory()->create([
            'user_id' => $userPro->id,
            'nombre' => 'Dra. Laura',
            'apellido' => 'Vargas',
        ]);

        $esp = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Limpieza de sarro',
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($this->servicio->id);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);
    }

    public function test_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/v1/mis-turnos');

        $response->assertStatus(401);
    }

    public function test_rol_no_paciente_devuelve_403(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $token = $funcionario->createToken('AdminDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertStatus(403);

        $profesionalUser = User::factory()->profesional()->create();
        $tokenPro = $profesionalUser->createToken('ProDevice')->plainTextToken;

        $responsePro = $this->withHeader('Authorization', "Bearer {$tokenPro}")
            ->getJson('/api/v1/mis-turnos');

        $responsePro->assertStatus(403);
    }

    public function test_paciente_ve_sus_turnos_proximos_por_defecto(): void
    {
        $inicioFuturo = Carbon::tomorrow('America/Asuncion')->setTime(10, 0);
        $finFuturo = $inicioFuturo->copy()->addMinutes(30);

        $turnoFuturo = Turno::factory()->create([
            'paciente_id' => $this->pacienteTitular->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicioFuturo,
            'fin' => $finFuturo,
            'estado' => EstadoTurno::Confirmado,
        ]);

        $inicioPasado = Carbon::yesterday('America/Asuncion')->setTime(10, 0);
        $finPasado = $inicioPasado->copy()->addMinutes(30);

        Turno::factory()->create([
            'paciente_id' => $this->pacienteTitular->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicioPasado,
            'fin' => $finPasado,
            'estado' => EstadoTurno::Atendido,
        ]);

        $token = $this->usuarioPaciente->createToken('iPhone')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($turnoFuturo->id, $data[0]['id']);
    }

    public function test_paciente_ve_turnos_de_familiares_dependientes(): void
    {
        $dependiente = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->usuarioPaciente->id,
            'nombre' => 'Lucas',
            'apellido' => 'González',
        ]);

        $inicio = Carbon::tomorrow('America/Asuncion')->setTime(11, 0);
        $fin = $inicio->copy()->addMinutes(30);

        $turnoDependiente = Turno::factory()->create([
            'paciente_id' => $dependiente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => EstadoTurno::Solicitado,
        ]);

        $token = $this->usuarioPaciente->createToken('iPhone')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($turnoDependiente->id, $data[0]['id']);
        $this->assertSame('Lucas González', $data[0]['paciente']['nombre']);
    }

    public function test_paciente_no_ve_turnos_de_otro_paciente_ajeno(): void
    {
        $otroUser = User::factory()->paciente()->create();
        $otroPaciente = Paciente::factory()->create(['user_id' => $otroUser->id]);

        $inicio = Carbon::tomorrow('America/Asuncion')->setTime(14, 0);
        Turno::factory()->create([
            'paciente_id' => $otroPaciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addMinutes(30),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $token = $this->usuarioPaciente->createToken('iPhone')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_parametro_historial_devuelve_turnos_pasados(): void
    {
        $inicioPasado = Carbon::yesterday('America/Asuncion')->setTime(10, 0);
        $finPasado = $inicioPasado->copy()->addMinutes(30);

        $turnoPasado = Turno::factory()->create([
            'paciente_id' => $this->pacienteTitular->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicioPasado,
            'fin' => $finPasado,
            'estado' => EstadoTurno::Atendido,
        ]);

        $token = $this->usuarioPaciente->createToken('iPhone')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos?historial=1');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($turnoPasado->id, $data[0]['id']);
    }

    public function test_formato_campos_turno(): void
    {
        $inicio = Carbon::tomorrow('America/Asuncion')->setTime(15, 30);
        $fin = $inicio->copy()->addMinutes(30);

        $turno = Turno::factory()->create([
            'paciente_id' => $this->pacienteTitular->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => EstadoTurno::Confirmado,
        ]);

        $token = $this->usuarioPaciente->createToken('iPhone')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'inicio',
                    'fin',
                    'estado' => [
                        'valor',
                        'etiqueta',
                    ],
                    'servicio' => [
                        'id',
                        'nombre',
                    ],
                    'profesional' => [
                        'id',
                        'nombre_completo',
                    ],
                    'paciente' => [
                        'id',
                        'nombre',
                    ],
                ],
            ],
            'links',
            'meta',
        ]);

        $data = $response->json('data.0');
        $this->assertSame($turno->id, $data['id']);
        $this->assertSame('confirmado', $data['estado']['valor']);
        $this->assertSame('Confirmado', $data['estado']['etiqueta']);
        $this->assertSame($this->servicio->id, $data['servicio']['id']);
        $this->assertSame('Limpieza de sarro', $data['servicio']['nombre']);
        $this->assertSame($this->profesional->id, $data['profesional']['id']);
        $this->assertSame('Dra. Laura Vargas', $data['profesional']['nombre_completo']);
        $this->assertSame($this->pacienteTitular->id, $data['paciente']['id']);
        $this->assertSame('María González', $data['paciente']['nombre']);
    }

    public function test_turnos_paginados_de_a_20(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $inicio = Carbon::tomorrow('America/Asuncion')->addDays($i)->setTime(9, 0);
            Turno::factory()->create([
                'paciente_id' => $this->pacienteTitular->id,
                'profesional_id' => $this->profesional->id,
                'servicio_id' => $this->servicio->id,
                'consultorio_id' => $this->consultorio->id,
                'inicio' => $inicio,
                'fin' => $inicio->copy()->addMinutes(30),
                'estado' => EstadoTurno::Confirmado,
            ]);
        }

        $token = $this->usuarioPaciente->createToken('iPhone')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/mis-turnos');

        $response->assertOk();
        $this->assertCount(20, $response->json('data'));
        $this->assertSame(25, $response->json('meta.total'));
        $this->assertSame(20, $response->json('meta.per_page'));
    }
}
