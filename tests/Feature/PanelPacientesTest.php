<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelPacientesTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $profesionalUser1;

    private Profesional $profesional1;

    private User $profesionalUser2;

    private Profesional $profesional2;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->funcionario = User::factory()->funcionario()->create();

        $this->profesional1 = Profesional::factory()->create(['orden' => 1]);
        $this->profesionalUser1 = $this->profesional1->user;

        $this->profesional2 = Profesional::factory()->create(['orden' => 2]);
        $this->profesionalUser2 = $this->profesional2->user;

        $esp = Especialidad::factory()->create();
        $this->servicio = Servicio::factory()->create(['especialidad_id' => $esp->id]);
        $this->profesional1->servicios()->attach($this->servicio);
        $this->profesional2->servicios()->attach($this->servicio);
    }

    public function test_funcionario_puede_listar_pacientes_paginados_y_buscar(): void
    {
        Paciente::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'Álvarez',
            'documento' => '1111111',
        ]);
        Paciente::factory()->create([
            'nombre' => 'Bernardo',
            'apellido' => 'Benítez',
            'documento' => '2222222',
        ]);
        Paciente::factory()->create([
            'nombre' => 'Carlos',
            'apellido' => 'Cabrera',
            'documento' => '3333333',
        ]);

        $response = $this->actingAs($this->funcionario)->get(route('panel.pacientes'));
        $response->assertOk();
        $response->assertSee('Pacientes');
        $response->assertSee('Álvarez');
        $response->assertSee('Benítez');
        $response->assertSee('Cabrera');

        // Búsqueda sin tilde (ana debe encontrar Álvarez)
        $respBusqueda = $this->actingAs($this->funcionario)->get(route('panel.pacientes', ['q' => 'alv']));
        $respBusqueda->assertOk();
        $respBusqueda->assertSee('Álvarez');
        $respBusqueda->assertDontSee('Benítez');

        // Búsqueda por documento
        $respDoc = $this->actingAs($this->funcionario)->get(route('panel.pacientes', ['q' => '222']));
        $respDoc->assertOk();
        $respDoc->assertSee('Benítez');
        $respDoc->assertDontSee('Cabrera');
    }

    public function test_profesional_solo_ve_pacientes_con_turnos_con_ella(): void
    {
        $paciente1 = Paciente::factory()->create(['nombre' => 'Paciente', 'apellido' => 'DeGloria']);
        $paciente2 = Paciente::factory()->create(['nombre' => 'Paciente', 'apellido' => 'DeAna']);

        Turno::factory()->create([
            'paciente_id' => $paciente1->id,
            'profesional_id' => $this->profesional1->id,
            'servicio_id' => $this->servicio->id,
            'inicio' => now()->addDays(2),
            'fin' => now()->addDays(2)->addMinutes(30),
            'estado' => EstadoTurno::Confirmado,
        ]);

        Turno::factory()->create([
            'paciente_id' => $paciente2->id,
            'profesional_id' => $this->profesional2->id,
            'servicio_id' => $this->servicio->id,
            'inicio' => now()->addDays(3),
            'fin' => now()->addDays(3)->addMinutes(30),
            'estado' => EstadoTurno::Confirmado,
        ]);

        // Profesional 1 solo ve al paciente 1
        $resp1 = $this->actingAs($this->profesionalUser1)->get(route('panel.pacientes'));
        $resp1->assertOk();
        $resp1->assertSee('DeGloria');
        $resp1->assertDontSee('DeAna');

        // Profesional 2 solo ve al paciente 2
        $resp2 = $this->actingAs($this->profesionalUser2)->get(route('panel.pacientes'));
        $resp2->assertOk();
        $resp2->assertSee('DeAna');
        $resp2->assertDontSee('DeGloria');
    }

    public function test_ficha_de_paciente_muestra_datos_familia_y_turnos(): void
    {
        $titularUser = User::factory()->paciente()->create(['email' => 'titular@test.com']);
        $titularPaciente = Paciente::factory()->create([
            'user_id' => $titularUser->id,
            'nombre' => 'María',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '4000123',
            'telefono' => '0981123456',
            'fecha_nacimiento' => now()->subYears(30)->toDateString(),
            'created_at' => now()->subMonths(3),
        ]);

        $hijoPaciente = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $titularUser->id,
            'nombre' => 'Joaquín',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => now()->subYears(5)->toDateString(),
        ]);

        Turno::factory()->create([
            'paciente_id' => $titularPaciente->id,
            'profesional_id' => $this->profesional1->id,
            'servicio_id' => $this->servicio->id,
            'estado' => EstadoTurno::Atendido,
            'inicio' => now()->subDays(10),
            'fin' => now()->subDays(10)->addMinutes(30),
        ]);

        Turno::factory()->create([
            'paciente_id' => $titularPaciente->id,
            'profesional_id' => $this->profesional1->id,
            'servicio_id' => $this->servicio->id,
            'estado' => EstadoTurno::Ausente,
            'inicio' => now()->subDays(5),
            'fin' => now()->subDays(5)->addMinutes(30),
        ]);

        $response = $this->actingAs($this->funcionario)->get(route('panel.pacientes.show', $titularPaciente));
        $response->assertOk();
        $response->assertSee('María Pérez');
        $response->assertSee('Paciente desde');
        $response->assertSee('Con cuenta');
        $response->assertSee('4000123');
        $response->assertSee('30 años');
        $response->assertSee('0981 123 456');
        $response->assertSee('titular@test.com');
        $response->assertSee('Joaquín');
        $response->assertSee('5 años');
        $response->assertSee('1 atendido');
        $response->assertSee('1 ausente');
        $response->assertSee('0 cancelados');

        // Ver ficha del hijo: debe decir "A cargo de María Pérez"
        $respHijo = $this->actingAs($this->funcionario)->get(route('panel.pacientes.show', $hijoPaciente));
        $respHijo->assertOk();
        $respHijo->assertSee('A cargo de');
        $respHijo->assertSee('María');
    }

    public function test_profesional_recibe_403_al_ver_ficha_de_paciente_sin_turnos_con_ella(): void
    {
        $pacienteAjeno = Paciente::factory()->create();
        Turno::factory()->create([
            'paciente_id' => $pacienteAjeno->id,
            'profesional_id' => $this->profesional2->id,
            'servicio_id' => $this->servicio->id,
            'inicio' => now()->subDay(),
            'fin' => now()->subDay()->addMinutes(30),
        ]);

        // Profesional 1 no tiene turnos con él -> 403
        $this->actingAs($this->profesionalUser1)
            ->get(route('panel.pacientes.show', $pacienteAjeno))
            ->assertForbidden();

        // Profesional 2 sí tiene turnos -> 200
        $this->actingAs($this->profesionalUser2)
            ->get(route('panel.pacientes.show', $pacienteAjeno))
            ->assertOk();
    }

    public function test_editar_contacto_valido_e_invalido_y_no_permite_cambiar_documento(): void
    {
        $paciente = Paciente::factory()->create([
            'user_id' => null,
            'documento' => '1234567',
            'telefono' => '0981000000',
        ]);

        // Intentar mandar datos alterados (documento y nombre no deben cambiar)
        $respValido = $this->actingAs($this->funcionario)
            ->put(route('panel.pacientes.contacto.update', $paciente), [
                'telefono' => '0982 774 586',
                'documento' => '9999999',
                'nombre' => 'Hackeado',
            ]);

        $respValido->assertRedirect(route('panel.pacientes.show', $paciente));

        $paciente->refresh();
        $this->assertSame('0982774586', $paciente->telefono);
        $this->assertSame('1234567', $paciente->documento); // No se modificó
        $this->assertNotSame('Hackeado', $paciente->nombre); // No se modificó

        // Teléfono inválido falla validación
        $respInvalido = $this->actingAs($this->funcionario)
            ->put(route('panel.pacientes.contacto.update', $paciente), [
                'telefono' => '12345',
            ]);
        $respInvalido->assertSessionHasErrors('telefono');
    }

    public function test_actualizar_contacto_sin_campo_telefono_no_borra_el_telefono_existente(): void
    {
        $paciente = Paciente::factory()->create([
            'user_id' => null,
            'documento' => '1234567',
            'telefono' => '0981000000',
        ]);

        $response = $this->actingAs($this->funcionario)
            ->put(route('panel.pacientes.contacto.update', $paciente), []);

        $response->assertRedirect(route('panel.pacientes.show', $paciente));
        $this->assertSame('0981000000', $paciente->fresh()->telefono);
    }

    public function test_boton_nuevo_turno_preselecciona_el_paciente(): void
    {
        $paciente = Paciente::factory()->create([
            'nombre' => 'Marta',
            'apellido' => 'Sosa',
            'documento' => '3344556',
        ]);

        $respFicha = $this->actingAs($this->funcionario)->get(route('panel.pacientes.show', $paciente));
        $respFicha->assertOk();
        $urlNuevoTurno = route('panel.turnos.crear', ['paciente' => $paciente->id]);
        $respFicha->assertSee($urlNuevoTurno);

        // Al visitar esa URL, el paciente llega preseleccionado
        $respCrear = $this->actingAs($this->funcionario)->get($urlNuevoTurno);
        $respCrear->assertOk();
        $respCrear->assertSee('Marta Sosa');
        $respCrear->assertSee('3344556');
    }
}
