<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Exceptions\HorarioNoDisponible;
use App\Models\Disponibilidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use App\Services\DisponibilidadService;
use App\Services\ProfesionalService;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BajaProfesionalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_baja_revoca_tokens_y_reactivar_conserva_perfil(): void
    {
        $prof = Profesional::factory()->create();
        $prof->user->createToken('sesion');
        $this->actingAs(User::factory()->funcionario()->create())
            ->post('/panel/profesionales/'.$prof->id.'/baja')->assertRedirect(route('panel.profesionales'));
        $this->assertFalse($prof->fresh()->activo);
        $this->assertSame(0, $prof->user->tokens()->count());
        $this->post('/panel/profesionales/'.$prof->id.'/reactivar')->assertRedirect(route('panel.profesionales'));
        $this->assertTrue($prof->fresh()->activo);
    }

    public function test_baja_bloqueada_incluye_inicio_igual_a_ahora_y_no_revoca_tokens(): void
    {
        $this->freezeTime();
        $prof = Profesional::factory()->create();
        $prof->user->createToken('sesion');
        Turno::factory()->create(['profesional_id' => $prof->id, 'inicio' => now(), 'fin' => now()->addMinutes(30)]);
        $this->actingAs(User::factory()->funcionario()->create())
            ->post('/panel/profesionales/'.$prof->id.'/baja')
            ->assertSessionHas('baja_bloqueada', fn ($data) => $data['cantidad'] === 1);
        $this->assertTrue($prof->fresh()->activo);
        $this->assertSame(1, $prof->user->tokens()->count());
        $this->get(route('panel.profesionales'))->assertSee('turnos a partir de hoy')->assertSee('Ir a la Agenda');
    }

    public function test_baja_permitida_con_pasados_y_cancelados_conserva_historial(): void
    {
        $prof = Profesional::factory()->create();
        $pasado = Turno::factory()->create(['profesional_id' => $prof->id, 'inicio' => now()->subDay(), 'fin' => now()->subDay()->addMinutes(30)]);
        Turno::factory()->create(['profesional_id' => $prof->id, 'estado' => EstadoTurno::Cancelado]);
        $user = User::factory()->funcionario()->create();
        $this->actingAs($user)->post('/panel/profesionales/'.$prof->id.'/baja');
        $this->assertFalse($prof->fresh()->activo);
        $this->get(route('panel.turnos.show', $pasado))->assertOk();
        $this->get(route('panel.pacientes.show', $pasado->paciente))->assertOk()->assertSee($prof->nombre);
        $this->get(route('panel.agenda', ['fecha' => $pasado->inicio->toDateString()]))->assertOk()->assertSee($prof->nombre);
    }

    public function test_solo_funcionario_puede_dar_baja_y_reactivar(): void
    {
        $prof = Profesional::factory()->create();
        foreach ([$prof->user, User::factory()->paciente()->create()] as $user) {
            foreach (['baja', 'reactivar'] as $accion) {
                $this->actingAs($user)->post('/panel/profesionales/'.$prof->id.'/'.$accion)->assertForbidden();
            }
        }
    }

    public function test_login_y_token_rechazados_con_mensaje_generico(): void
    {
        $prof = Profesional::factory()->create(['activo' => false]);
        $this->post('/login', ['email' => $prof->user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
        $this->assertGuest();
        $this->postJson('/api/v1/token', ['email' => $prof->user->email, 'password' => 'password', 'nombre_dispositivo' => 'prueba'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_sesion_abierta_queda_sin_acceso(): void
    {
        $prof = Profesional::factory()->create();
        $this->actingAs($prof->user)->get('/panel')->assertOk();
        $prof->update(['activo' => false]);
        $this->get('/panel')->assertForbidden();
    }

    public function test_inactiva_no_se_ofrece_y_reactivada_recupera_disponibilidad(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(7));
        $paciente = Paciente::factory()->create();
        $prof = Profesional::factory()->create();
        $servicio = Servicio::factory()->create(['activo' => true]);
        $prof->servicios()->attach($servicio);
        $fecha = now()->addDay()->setTime(9, 0);
        Disponibilidad::factory()->create(['profesional_id' => $prof->id, 'dia_semana' => $fecha->dayOfWeekIso, 'hora_inicio' => '08:00', 'hora_fin' => '18:00']);
        $disponibilidad = app(DisponibilidadService::class);
        $this->assertTrue($disponibilidad->estaLibre($prof, $servicio, $fecha));
        $prof->update(['activo' => false]);
        $this->assertSame([], $disponibilidad->franjasLibres($prof, $servicio, $fecha));
        $this->assertFalse($disponibilidad->estaLibre($prof, $servicio, $fecha));
        $this->assertNull(app(ReservaService::class)->opciones($prof->id, $servicio->id, $fecha->toDateString())['profesional']);
        $this->get('/')->assertDontSee($prof->nombreCompleto());
        $this->get('/servicios')->assertDontSee($prof->nombreCompleto());
        $this->getJson('/api/v1/profesionales')->assertJsonMissing(['id' => $prof->id]);
        $this->getJson('/api/v1/disponibilidad?'.http_build_query(['profesional_id' => $prof->id, 'servicio_id' => $servicio->id, 'fecha' => $fecha->toDateString()]))->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs(User::factory()->paciente()->create())->get('/turnos/nuevo')->assertDontSee($prof->nombreCompleto());
        $this->actingAs(User::factory()->funcionario()->create())->get(route('panel.turnos.crear', ['paciente' => $paciente->id]))->assertDontSee($prof->nombreCompleto())
            ->assertViewHas('profesionales', fn ($lista) => ! $lista->contains('id', $prof->id));
        $this->get(route('panel.profesionales'))->assertSee('Dada de baja')->assertSee('Reactivar')->assertDontSee(route('panel.profesionales.servicios', $prof));
        $this->post(route('panel.profesionales.reactivar', $prof))->assertRedirect(route('panel.profesionales'));
        $this->assertTrue($disponibilidad->estaLibre($prof, $servicio, $fecha));
        $this->assertNotNull(app(ReservaService::class)->opciones($prof->id, null, null)['profesional']);
        $this->getJson('/api/v1/profesionales')->assertJsonFragment(['id' => $prof->id]);
        $this->get('/')->assertSee($prof->nombreCompleto());
        $this->get('/servicios')->assertSee($prof->nombreCompleto());
        $this->get(route('panel.turnos.crear', ['paciente' => $paciente->id]))->assertSee($prof->nombreCompleto());
        $this->actingAs(User::factory()->paciente()->create())->get('/turnos/nuevo')->assertSee($prof->nombreCompleto());
    }

    public function test_catalogo_api_no_expone_ids_de_inactivas_y_listado_las_deja_al_final(): void
    {
        $inactiva = Profesional::factory()->create(['activo' => false, 'orden' => 1]);
        $activa = Profesional::factory()->create(['orden' => 999]);
        $servicio = Servicio::factory()->create();
        $inactiva->servicios()->attach($servicio);
        $activa->servicios()->attach($servicio);
        $this->getJson('/api/v1/servicios')->assertJsonPath('data.0.profesionales_ids', [$activa->id]);
        $this->actingAs(User::factory()->funcionario()->create())->get(route('panel.profesionales'))
            ->assertSeeInOrder([$activa->nombreCompleto(), $inactiva->nombreCompleto()]);
    }

    public function test_agenda_sin_activas_no_falla_ni_elige_inactiva_por_defecto(): void
    {
        Profesional::factory()->create(['activo' => false]);
        $this->actingAs(User::factory()->funcionario()->create())->get(route('panel.agenda', ['vista' => 'semana']))
            ->assertOk()->assertViewHas('vista', 'dia');
    }

    public function test_agenda_por_defecto_excluye_inactiva_sin_turnos_del_dia(): void
    {
        $inactiva = Profesional::factory()->create(['activo' => false]);
        $activa = Profesional::factory()->create();
        $this->actingAs(User::factory()->funcionario()->create())->get(route('panel.agenda'))
            ->assertViewHas('profesionales', fn ($lista) => $lista->modelKeys() === [$activa->id]);
    }

    public function test_reserva_con_modelo_anterior_a_la_baja_no_crea_turno(): void
    {
        $prof = Profesional::factory()->create();
        $servicio = Servicio::factory()->create();
        $prof->servicios()->attach($servicio);
        $inicio = now()->addDay()->setTime(9, 0);
        Disponibilidad::factory()->create(['profesional_id' => $prof->id, 'dia_semana' => $inicio->dayOfWeekIso, 'hora_inicio' => '08:00', 'hora_fin' => '18:00']);
        app(ProfesionalService::class)->baja($prof);
        $this->assertFalse(app(DisponibilidadService::class)->estaLibre($prof, $servicio, $inicio));
        $this->expectException(HorarioNoDisponible::class);
        app(ReservaService::class)->agendar($prof, $servicio, Paciente::factory()->create(), $inicio, User::factory()->funcionario()->create());
    }

    public function test_disponibilidad_sin_activas_muestra_estado_vacio(): void
    {
        Profesional::factory()->create(['activo' => false]);
        $this->actingAs(User::factory()->funcionario()->create())->get(route('panel.disponibilidad'))
            ->assertOk()->assertSee('No hay profesionales activas');
    }

    public function test_baja_bloqueada_por_cualquier_estado_no_cancelado_futuro(): void
    {
        $prof = Profesional::factory()->create();
        foreach ([EstadoTurno::Solicitado, EstadoTurno::Confirmado, EstadoTurno::Atendido, EstadoTurno::Ausente] as $i => $estado) {
            Turno::factory()->create(['profesional_id' => $prof->id, 'estado' => $estado,
                'inicio' => now()->addDays($i + 1), 'fin' => now()->addDays($i + 1)->addMinutes(30)]);
        }
        $this->actingAs(User::factory()->funcionario()->create())->post(route('panel.profesionales.baja', $prof))
            ->assertSessionHas('baja_bloqueada', fn ($data) => $data['cantidad'] === 4);
        $this->assertTrue($prof->fresh()->activo);
    }
}
