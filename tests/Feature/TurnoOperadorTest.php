<?php

namespace Tests\Feature;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use App\Jobs\EnviarNotificacion;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TurnoOperadorTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $profesionalUser1;

    private Profesional $profesional1;

    private User $profesionalUser2;

    private Profesional $profesional2;

    private User $pacienteUser;

    private Paciente $paciente;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->funcionario = User::factory()->funcionario()->create(['email' => 'admin@glowdental.test']);

        $this->profesionalUser1 = User::factory()->profesional()->create(['email' => 'dra.ana@glowdental.test']);
        $this->profesional1 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser1->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->profesionalUser2 = User::factory()->profesional()->create(['email' => 'dra.gloria@glowdental.test']);
        $this->profesional2 = Profesional::factory()->create([
            'user_id' => $this->profesionalUser2->id,
            'nombre' => 'Gloria',
            'apellido' => 'Godoy',
        ]);

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'carlos@paciente.test']);
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
            'documento' => '4123456',
            'telefono' => '0981111111',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);

        $this->profesional1->servicios()->attach($this->servicio->id);
        $this->profesional2->servicios()->attach($this->servicio->id);

        // Disponibilidad: Lunes (1) y Viernes (5) de 08:00 a 12:00
        foreach ([$this->profesional1, $this->profesional2] as $prof) {
            Disponibilidad::factory()->create([
                'profesional_id' => $prof->id,
                'consultorio_id' => $this->consultorio->id,
                'dia_semana' => 1,
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
            ]);
            Disponibilidad::factory()->create([
                'profesional_id' => $prof->id,
                'consultorio_id' => $this->consultorio->id,
                'dia_semana' => 5,
                'hora_inicio' => '08:00',
                'hora_fin' => '12:00',
            ]);
        }
    }

    public function test_invitado_redirige_a_login_y_paciente_recibe_403(): void
    {
        $this->get(route('panel.turnos.crear'))->assertRedirect(route('login'));
        $this->post(route('panel.turnos.store'), [])->assertRedirect(route('login'));
        $this->post(route('panel.pacientes.store'), [])->assertRedirect(route('login'));

        $this->actingAs($this->pacienteUser)->get(route('panel.turnos.crear'))->assertForbidden();
        $this->actingAs($this->pacienteUser)->post(route('panel.turnos.store'), [])->assertForbidden();
        $this->actingAs($this->pacienteUser)->post(route('panel.pacientes.store'), [])->assertForbidden();
    }

    public function test_busqueda_de_paciente_por_apellido_y_por_documento(): void
    {
        $otroPaciente = Paciente::factory()->create([
            'nombre' => 'Lucía',
            'apellido' => 'Benítez',
            'documento' => '5432100',
        ]);

        // Búsqueda con menos de 3 caracteres no arroja resultados
        $resCorto = $this->actingAs($this->funcionario)
            ->get(route('panel.turnos.crear', ['q' => 'go']));
        $resCorto->assertOk();
        $resCorto->assertDontSee('4123456');

        // Búsqueda por apellido 'Gom'
        $resApellido = $this->actingAs($this->funcionario)
            ->get(route('panel.turnos.crear', ['q' => 'Gom']));
        $resApellido->assertOk();
        $resApellido->assertSee('Carlos Gómez');
        $resApellido->assertSee('4123456');
        $resApellido->assertDontSee('Lucía Benítez');

        // Búsqueda por documento '5432'
        $resDoc = $this->actingAs($this->funcionario)
            ->get(route('panel.turnos.crear', ['q' => '5432']));
        $resDoc->assertOk();
        $resDoc->assertSee('Lucía Benítez');
        $resDoc->assertSee('5432100');
        $resDoc->assertDontSee('Carlos Gómez');
    }

    public function test_busqueda_de_paciente_escapa_comodines_de_like(): void
    {
        Paciente::factory()->create([
            'nombre' => 'Ana_María',
            'apellido' => 'Especial',
            'documento' => '999%888',
        ]);

        $resGuionBajo = $this->actingAs($this->funcionario)
            ->get(route('panel.turnos.crear', ['q' => 'na_M']));
        $resGuionBajo->assertOk();
        $resGuionBajo->assertSee('Ana_María');

        $resPorcentaje = $this->actingAs($this->funcionario)
            ->get(route('panel.turnos.crear', ['q' => '99%8']));
        $resPorcentaje->assertOk();
        $resPorcentaje->assertSee('999%888');
    }

    public function test_alta_de_paciente_sin_cuenta_sin_responsable(): void
    {
        $response = $this->actingAs($this->funcionario)
            ->post(route('panel.pacientes.store'), [
                'nombre' => 'Mario',
                'apellido' => 'Brizuela',
                'tipo_documento' => 'CI',
                'documento' => '1234567',
                'telefono' => '0981222333',
                'fecha_nacimiento' => '1990-05-15',
            ]);

        $nuevo = Paciente::where('documento', '1234567')->first();
        $this->assertNotNull($nuevo);
        $this->assertNull($nuevo->user_id);
        $this->assertNull($nuevo->responsable_id);

        $response->assertRedirect(route('panel.turnos.crear', ['paciente' => $nuevo->id]));
    }

    public function test_alta_de_paciente_sin_cuenta_con_responsable(): void
    {
        $response = $this->actingAs($this->funcionario)
            ->post(route('panel.pacientes.store'), [
                'nombre' => 'Mateo',
                'apellido' => 'Gómez',
                'tipo_documento' => 'CI',
                'documento' => '7654321',
                'telefono' => '0981111111',
                'fecha_nacimiento' => '2018-05-15',
                'responsable_documento' => $this->paciente->documento, // Carlos Gómez tiene cuenta
            ]);

        $nuevo = Paciente::where('documento', '7654321')->first();
        $this->assertNotNull($nuevo);
        $this->assertSame($this->pacienteUser->id, $nuevo->responsable_id);

        $response->assertRedirect(route('panel.turnos.crear', ['paciente' => $nuevo->id]));
    }

    public function test_alta_paciente_responsable_sin_cuenta_o_inexistente_falla_validacion(): void
    {
        $pacienteSinCuenta = Paciente::factory()->create([
            'user_id' => null,
            'documento' => '9999999',
        ]);

        $response = $this->actingAs($this->funcionario)
            ->from(route('panel.turnos.crear', ['nuevo_paciente' => 1]))
            ->post(route('panel.pacientes.store'), [
                'nombre' => 'Mateo',
                'apellido' => 'Gómez',
                'tipo_documento' => 'CI',
                'documento' => '7654321',
                'telefono' => '0981111111',
                'fecha_nacimiento' => '2018-05-15',
                'responsable_documento' => '9999999', // No tiene user_id
            ]);

        $response->assertSessionHasErrors('responsable_documento');
        $this->assertDatabaseMissing('pacientes', ['documento' => '7654321']);
    }

    public function test_alta_paciente_documento_repetido_falla_validacion(): void
    {
        $response = $this->actingAs($this->funcionario)
            ->from(route('panel.turnos.crear', ['nuevo_paciente' => 1]))
            ->post(route('panel.pacientes.store'), [
                'nombre' => 'Otro',
                'apellido' => 'Gómez',
                'tipo_documento' => 'CI',
                'documento' => $this->paciente->documento, // Repetido con mismo tipo
                'telefono' => '0981111111',
                'fecha_nacimiento' => '1995-01-01',
            ]);

        $response->assertSessionHasErrors(['documento' => 'Ya existe un paciente con ese documento.']);
    }

    public function test_alta_paciente_mismo_documento_con_distinto_tipo_pasa(): void
    {
        $response = $this->actingAs($this->funcionario)
            ->post(route('panel.pacientes.store'), [
                'nombre' => 'Extranjero',
                'apellido' => 'Gómez',
                'tipo_documento' => 'pasaporte',
                'documento' => $this->paciente->documento, // Mismo número pero pasaporte
                'telefono' => '0981111111',
                'fecha_nacimiento' => '1995-01-01',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pacientes', [
            'documento' => $this->paciente->documento,
            'tipo_documento' => 'pasaporte',
        ]);
    }

    public function test_funcionario_agenda_para_cualquier_profesional_turno_confirmado_historial_y_notificacion(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->funcionario)
            ->post(route('panel.turnos.store'), [
                'paciente_id' => $this->paciente->id,
                'profesional' => $this->profesional2->id,
                'servicio' => $this->servicio->id,
                'fecha' => '2026-10-23',
                'hora' => '09:00',
            ]);

        $turno = Turno::where('paciente_id', $this->paciente->id)
            ->where('profesional_id', $this->profesional2->id)
            ->first();

        $this->assertNotNull($turno);
        $response->assertRedirect(route('panel.turnos.show', $turno));
        $response->assertSessionHas('success', 'Turno agendado y confirmado.');

        $this->assertSame(EstadoTurno::Confirmado, $turno->estado);
        $this->assertSame('2026-10-23 09:00', $turno->inicio->format('Y-m-d H:i'));

        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'estado_anterior' => null,
            'estado_nuevo' => EstadoTurno::Confirmado->value,
            'usuario_id' => $this->funcionario->id,
            'detalle' => 'Agendado por teléfono o en el consultorio',
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente->value,
        ]);

        Queue::assertPushed(EnviarNotificacion::class);
    }

    public function test_profesional_no_puede_agendar_para_otra_profesional_se_fuerza_la_suya(): void
    {
        Queue::fake();

        // Profesional 1 intenta mandar profesional = profesional 2 en el request
        $response = $this->actingAs($this->profesionalUser1)
            ->post(route('panel.turnos.store'), [
                'paciente_id' => $this->paciente->id,
                'profesional' => $this->profesional2->id,
                'servicio' => $this->servicio->id,
                'fecha' => '2026-10-23',
                'hora' => '09:00',
            ]);

        $turno = Turno::where('paciente_id', $this->paciente->id)->first();
        $this->assertNotNull($turno);
        $response->assertRedirect(route('panel.turnos.show', $turno));

        // Debe haberse forzado la profesional 1
        $this->assertSame($this->profesional1->id, $turno->profesional_id);
    }

    public function test_horario_ocupado_muestra_error_y_no_crea(): void
    {
        // Turno ya confirmado en ese horario
        Turno::create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional1->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => '2026-10-23 09:00:00',
            'fin' => '2026-10-23 09:30:00',
            'estado' => EstadoTurno::Confirmado,
        ]);

        $response = $this->actingAs($this->funcionario)
            ->post(route('panel.turnos.store'), [
                'paciente_id' => $this->paciente->id,
                'profesional' => $this->profesional1->id,
                'servicio' => $this->servicio->id,
                'fecha' => '2026-10-23',
                'hora' => '09:00',
            ]);

        $response->assertRedirect(route('panel.turnos.crear', [
            'paciente' => $this->paciente->id,
            'profesional' => $this->profesional1->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-23',
        ]));
        $response->assertSessionHas('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');
        $this->assertSame(1, Turno::count());
    }

    public function test_fuera_de_disponibilidad_falla(): void
    {
        // 07:00 está fuera del horario de atención (08:00 a 12:00)
        $response = $this->actingAs($this->funcionario)
            ->post(route('panel.turnos.store'), [
                'paciente_id' => $this->paciente->id,
                'profesional' => $this->profesional1->id,
                'servicio' => $this->servicio->id,
                'fecha' => '2026-10-23',
                'hora' => '07:00',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Turno::count());
    }

    public function test_item_nuevo_turno_aparece_en_el_menu_del_panel(): void
    {
        $resFunc = $this->actingAs($this->funcionario)->get(route('panel.turnos.crear'));
        $resFunc->assertOk();
        $resFunc->assertSee('Nuevo turno');

        $resProf = $this->actingAs($this->profesionalUser1)->get(route('panel.turnos.crear'));
        $resProf->assertOk();
        $resProf->assertSee('Nuevo turno');
    }
}
