<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Exceptions\HorarioNoDisponible;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitarTurnoTest extends TestCase
{
    use RefreshDatabase;

    private User $pacienteUser;

    private Paciente $paciente;

    private Profesional $profesional;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->pacienteUser = User::factory()->paciente()->create();
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'María',
            'apellido' => 'Rojas',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);
        $this->profesional = Profesional::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($this->servicio->id);

        // Lunes de 08:00 a 12:00
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_invitado_y_profesional_no_pueden_solicitar_turno(): void
    {
        $payload = [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ];

        $this->post('/turnos', $payload)->assertRedirect(route('login'));

        $profesionalUser = $this->profesional->user;
        $this->actingAs($profesionalUser)->post('/turnos', $payload)->assertForbidden();
    }

    public function test_paciente_crea_turno_solicitado_con_historial_y_redirige_a_show(): void
    {
        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ]);

        $turno = Turno::first();
        $this->assertNotNull($turno);

        $response->assertRedirect(route('paciente.turnos.show', $turno));

        $this->assertDatabaseHas('turnos', [
            'id' => $turno->id,
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => '2026-10-19 09:00:00-03',
            'fin' => '2026-10-19 09:30:00-03',
            'estado' => EstadoTurno::Solicitado->value,
        ]);

        $this->assertDatabaseHas('historial_turnos', [
            'turno_id' => $turno->id,
            'estado_anterior' => null,
            'estado_nuevo' => EstadoTurno::Solicitado->value,
            'usuario_id' => $this->pacienteUser->id,
        ]);
    }

    public function test_show_muestra_los_datos_del_turno(): void
    {
        $turno = Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-23 09:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-23 09:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        $response = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos.show', $turno));

        $response->assertOk();
        $response->assertSee('Tu turno fue solicitado');
        $response->assertSee('Solicitado');
        $response->assertSee('Viernes 23 de octubre, 09:00');
        $response->assertSee('Ana González');
        $response->assertSee('Consulta general · 30 min');
        $response->assertSee('María Rojas');
        $response->assertSee('La clínica lo revisa y lo confirma. Vas a ver el cambio de estado en tu inicio.');
        $response->assertSee(route('paciente.inicio'));
        $response->assertSee(route('paciente.reservar'));
    }

    public function test_otro_paciente_recibe_403_en_show(): void
    {
        $turno = Turno::factory()->create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        $otroUsuario = User::factory()->paciente()->create();

        $response = $this->actingAs($otroUsuario)->get(route('paciente.turnos.show', $turno));

        $response->assertForbidden();
    }

    public function test_responsable_puede_ver_turno_de_su_dependiente_en_show(): void
    {
        $dependiente = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Lucas',
            'apellido' => 'Rojas',
        ]);

        $turno = Turno::factory()->create([
            'paciente_id' => $dependiente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Solicitado,
        ]);

        $response = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos.show', $turno));

        $response->assertOk();
        $response->assertSee('Lucas Rojas');
        $response->assertSee('El turno de Lucas fue solicitado');
    }

    public function test_show_renderiza_textos_segun_estado_para_titular_y_familiar(): void
    {
        $dependiente = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Lucas',
            'apellido' => 'Rojas',
        ]);

        $casos = [
            [
                'estado' => EstadoTurno::Confirmado,
                'titular_titulo' => 'Tu turno está confirmado',
                'titular_sub' => 'Te esperamos. Si no podés venir, cancelalo con anticipación.',
                'familiar_titulo' => 'El turno de Lucas está confirmado',
                'familiar_sub' => 'Te esperamos. Si no podés venir, cancelalo con anticipación.',
            ],
            [
                'estado' => EstadoTurno::Cancelado,
                'titular_titulo' => 'Este turno fue cancelado',
                'titular_sub' => null,
                'familiar_titulo' => 'El turno de Lucas fue cancelado',
                'familiar_sub' => null,
            ],
            [
                'estado' => EstadoTurno::Atendido,
                'titular_titulo' => 'Turno atendido',
                'titular_sub' => null,
                'familiar_titulo' => 'El turno de Lucas fue atendido',
                'familiar_sub' => null,
            ],
            [
                'estado' => EstadoTurno::Ausente,
                'titular_titulo' => 'No asististe a este turno',
                'titular_sub' => null,
                'familiar_titulo' => 'Lucas no asistió a este turno',
                'familiar_sub' => null,
            ],
        ];

        $offset = 1;
        foreach ($casos as $caso) {
            $turnoTitular = Turno::factory()->create([
                'paciente_id' => $this->paciente->id,
                'profesional_id' => $this->profesional->id,
                'servicio_id' => $this->servicio->id,
                'consultorio_id' => $this->consultorio->id,
                'inicio' => Carbon::parse('2026-10-25 10:00:00', config('app.timezone'))->addDays($offset),
                'fin' => Carbon::parse('2026-10-25 10:30:00', config('app.timezone'))->addDays($offset),
                'estado' => $caso['estado'],
            ]);

            $resTitular = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos.show', $turnoTitular));
            $resTitular->assertOk();
            $resTitular->assertSee($caso['titular_titulo']);
            if ($caso['titular_sub']) {
                $resTitular->assertSee($caso['titular_sub']);
            } else {
                $resTitular->assertDontSee('La clínica lo revisa y lo confirma');
            }

            $offset++;
            $turnoFamiliar = Turno::factory()->create([
                'paciente_id' => $dependiente->id,
                'profesional_id' => $this->profesional->id,
                'servicio_id' => $this->servicio->id,
                'consultorio_id' => $this->consultorio->id,
                'inicio' => Carbon::parse('2026-10-25 10:00:00', config('app.timezone'))->addDays($offset),
                'fin' => Carbon::parse('2026-10-25 10:30:00', config('app.timezone'))->addDays($offset),
                'estado' => $caso['estado'],
            ]);

            $resFamiliar = $this->actingAs($this->pacienteUser)->get(route('paciente.turnos.show', $turnoFamiliar));
            $resFamiliar->assertOk();
            $resFamiliar->assertSee($caso['familiar_titulo']);
            if ($caso['familiar_sub']) {
                $resFamiliar->assertSee($caso['familiar_sub']);
            } else {
                $resFamiliar->assertDontSee('La clínica lo revisa y lo confirma');
            }

            $offset++;
        }
    }

    public function test_horario_ocupado_por_turno_confirmado_redirige_con_error_y_no_crea_turno(): void
    {
        Turno::factory()->create([
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:30:00', config('app.timezone')),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertRedirect(route('paciente.reservar', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'para' => $this->paciente->id,
        ]));
        $response->assertSessionHas('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');

        $this->assertSame(1, Turno::count());
    }

    public function test_hora_fuera_de_disponibilidad_falla_con_error(): void
    {
        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '07:00',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertRedirect(route('paciente.reservar', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'para' => $this->paciente->id,
        ]));
        $response->assertSessionHas('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');

        $this->assertSame(0, Turno::count());
    }

    public function test_hora_desalineada_del_intervalo_falla_con_error(): void
    {
        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:07',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertRedirect(route('paciente.reservar', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'para' => $this->paciente->id,
        ]));
        $response->assertSessionHas('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');

        $this->assertSame(0, Turno::count());
    }

    public function test_paciente_id_de_otro_paciente_falla_validacion(): void
    {
        $otroPaciente = Paciente::factory()->create();

        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $otroPaciente->id,
        ]);

        $response->assertSessionHasErrors('paciente_id');
        $this->assertSame(0, Turno::count());
    }

    public function test_fecha_pasada_falla_validacion(): void
    {
        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-17',
            'hora' => '09:00',
            'paciente_id' => $this->paciente->id,
        ]);

        $response->assertSessionHasErrors('fecha');
        $this->assertSame(0, Turno::count());
    }

    public function test_exclusion_constraint_en_bd_es_capturada_como_horario_no_disponible(): void
    {
        $pdoException = new \PDOException('conflicting key value violates exclusion constraint "turnos_sin_solapamiento"', 0);
        $pdoException->errorInfo = ['23P01', 7, 'conflicting key value violates exclusion constraint "turnos_sin_solapamiento"'];
        $queryException = new QueryException(
            'pgsql',
            'insert into "turnos"',
            [],
            $pdoException
        );

        Turno::creating(function () use ($queryException) {
            throw $queryException;
        });

        $this->expectException(HorarioNoDisponible::class);

        app(ReservaService::class)->solicitar(
            $this->profesional,
            $this->servicio,
            $this->paciente,
            Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            $this->pacienteUser,
        );
    }

    public function test_boton_y_para_quien_es_el_turno_aparecen_en_reservar_con_fecha_elegida(): void
    {
        $response = $this->actingAs($this->pacienteUser)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
        ]));

        $response->assertOk();
        $response->assertSee('¿Para quién es el turno?');
        $response->assertSee('Para mí');
        $response->assertSee('María Rojas');
        $response->assertSee('Solicitar turno');
        $response->assertSee('value="'.$this->paciente->id.'"', false);
    }

    public function test_el_boton_de_solicitar_va_alineado_a_la_derecha_como_en_el_panel(): void
    {
        $html = $this->actingAs($this->pacienteUser)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
        ]))->getContent();

        $this->assertMatchesRegularExpression('/<div x-show="horaSeleccionada"[^>]*class="[^"]*\bjustify-end\b/', $html);
    }

    public function test_tutor_puede_solicitar_para_su_familiar(): void
    {
        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Lucas',
            'apellido' => 'Rojas',
        ]);

        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $familiar->id,
        ]);

        $turno = Turno::latest('id')->first();
        $response->assertRedirect(route('paciente.turnos.show', $turno));
        $this->assertSame($familiar->id, $turno->paciente_id);
    }

    public function test_tutor_no_puede_solicitar_para_el_familiar_de_otro(): void
    {
        $otroUser = User::factory()->paciente()->create();
        $familiarDeOtro = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $otroUser->id,
            'nombre' => 'Sofi',
            'apellido' => 'Pérez',
        ]);

        $response = $this->actingAs($this->pacienteUser)->post('/turnos', [
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
            'hora' => '09:00',
            'paciente_id' => $familiarDeOtro->id,
        ]);

        $response->assertSessionHasErrors('paciente_id');
        $this->assertSame(0, Turno::count());
    }

    public function test_el_formulario_muestra_la_opcion_del_familiar(): void
    {
        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Lucas',
            'apellido' => 'Rojas',
        ]);

        $response = $this->actingAs($this->pacienteUser)->get('/turnos/nuevo?'.http_build_query([
            'profesional' => $this->profesional->id,
            'servicio' => $this->servicio->id,
            'fecha' => '2026-10-19',
        ]));

        $response->assertOk();
        $response->assertSee('Para mí');
        $response->assertSee('María Rojas');
        $response->assertSee('Para Lucas');
        $response->assertSee('Lucas Rojas');
        $response->assertSee('value="'.$familiar->id.'"', false);
    }
}
