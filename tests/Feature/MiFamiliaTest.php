<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
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
use Tests\TestCase;

class MiFamiliaTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private Paciente $titular;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->tutor = User::factory()->paciente()->create();
        $this->titular = Paciente::factory()->create([
            'user_id' => $this->tutor->id,
            'nombre' => 'Laura',
            'apellido' => 'Gómez',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function familiarDe(User $user, array $datos = []): Paciente
    {
        return Paciente::factory()->create(array_merge([
            'user_id' => null,
            'responsable_id' => $user->id,
            'telefono' => null,
        ], $datos));
    }

    /**
     * @return array<string, string>
     */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Tomás',
            'apellido' => 'Gómez',
            'tipo_documento' => 'CI',
            'documento' => '7123456',
            'fecha_nacimiento' => '2021-03-12',
        ], $cambios);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('paciente.familia'))->assertRedirect(route('login'));
    }

    public function test_personal_de_la_clinica_no_accede(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $this->actingAs($funcionario)->get(route('paciente.familia'))->assertForbidden();
    }

    public function test_menu_del_paciente_muestra_mi_familia(): void
    {
        $this->actingAs($this->tutor)
            ->get(route('paciente.inicio'))
            ->assertOk()
            ->assertSee(route('paciente.familia'), false)
            ->assertSee('Mi familia');
    }

    public function test_sin_familiares_muestra_estado_vacio(): void
    {
        $this->actingAs($this->tutor)
            ->get(route('paciente.familia'))
            ->assertOk()
            ->assertSee('Todavía no agregaste a nadie')
            ->assertSee('Agregar familiar');
    }

    public function test_lista_solo_a_los_familiares_propios(): void
    {
        $this->familiarDe($this->tutor, [
            'nombre' => 'Sofía',
            'apellido' => 'Gómez',
            'documento' => '6345678',
            'fecha_nacimiento' => '2018-05-02',
        ]);

        $otroTutor = User::factory()->paciente()->create();
        $this->familiarDe($otroTutor, ['nombre' => 'Ajeno', 'apellido' => 'Pérez']);

        $this->actingAs($this->tutor)
            ->get(route('paciente.familia'))
            ->assertOk()
            ->assertSee('Sofía Gómez')
            ->assertSee('8 años')
            ->assertSee('CI 6345678')
            ->assertSee('SG')
            ->assertDontSee('Ajeno Pérez')
            ->assertDontSee('Todavía no agregaste a nadie');
    }

    public function test_muestra_el_proximo_turno_del_familiar_con_su_estado(): void
    {
        $sofia = $this->familiarDe($this->tutor, ['nombre' => 'Sofía']);
        $tomas = $this->familiarDe($this->tutor, ['nombre' => 'Tomás']);

        $servicio = Servicio::factory()->create(['nombre' => 'Consulta de ortodoncia']);
        Turno::factory()->create([
            'paciente_id' => $sofia->id,
            'servicio_id' => $servicio->id,
            'inicio' => Carbon::parse('2026-10-26 08:30', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-26 09:00', config('app.timezone')),
            'estado' => EstadoTurno::Confirmado,
        ]);
        // Los cancelados no cuentan como próximo turno
        Turno::factory()->create([
            'paciente_id' => $tomas->id,
            'estado' => EstadoTurno::Cancelado,
        ]);

        $this->actingAs($this->tutor)
            ->get(route('paciente.familia'))
            ->assertOk()
            ->assertSee('Consulta de ortodoncia')
            ->assertSee('08:30')
            ->assertSee('Confirmado')
            ->assertSee('Sin turnos próximos');
    }

    public function test_pedir_turno_lleva_a_reservar_con_el_familiar_elegido(): void
    {
        $sofia = $this->familiarDe($this->tutor, ['nombre' => 'Sofía']);

        $this->actingAs($this->tutor)
            ->get(route('paciente.familia'))
            ->assertSee(route('paciente.reservar', ['para' => $sofia->id]), false);
    }

    public function test_agrega_un_familiar_a_cargo(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('paciente.familia.store'), $this->datosValidos())
            ->assertRedirect(route('paciente.familia'))
            ->assertSessionHas('success');

        $familiar = Paciente::where('documento', '7123456')->firstOrFail();
        $this->assertSame($this->tutor->id, $familiar->responsable_id);
        $this->assertNull($familiar->user_id);
        $this->assertSame('Tomás', $familiar->nombre);
        $this->assertSame('2021-03-12', $familiar->fecha_nacimiento->toDateString());
    }

    public function test_valida_los_datos_del_familiar(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('paciente.familia.store'), [
                'nombre' => '',
                'apellido' => '',
                'tipo_documento' => 'DNI',
                'documento' => '',
                'fecha_nacimiento' => '2026-12-01',
            ])
            ->assertSessionHasErrors(['nombre', 'apellido', 'tipo_documento', 'documento', 'fecha_nacimiento']);

        $this->assertSame(1, Paciente::count());
    }

    public function test_documento_repetido_no_se_duplica_ni_se_vincula(): void
    {
        $existente = Paciente::factory()->create(['documento' => '6543210', 'tipo_documento' => 'CI']);

        $this->actingAs($this->tutor)
            ->post(route('paciente.familia.store'), $this->datosValidos(['documento' => '6543210']))
            ->assertSessionHasErrors([
                'documento' => 'No pudimos agregar a esa persona con esos datos. Escribinos por WhatsApp y lo resolvemos.',
            ]);

        $this->assertSame(1, Paciente::where('documento', '6543210')->count());
        $this->assertNull($existente->fresh()->responsable_id);
    }

    public function test_documento_repetido_con_otro_campo_invalido_no_revela_si_el_documento_existe(): void
    {
        Paciente::factory()->create(['documento' => '6543210', 'tipo_documento' => 'CI']);

        $conExistente = $this->actingAs($this->tutor)
            ->post(route('paciente.familia.store'), $this->datosValidos([
                'documento' => '6543210',
                'fecha_nacimiento' => '2026-12-01',
            ]));
        $conExistente->assertSessionHasErrors('fecha_nacimiento');
        $conExistente->assertSessionDoesntHaveErrors('documento');
        $erroresConExistente = $conExistente->getSession()->get('errors')->toArray();

        $conInexistente = $this->actingAs($this->tutor)
            ->post(route('paciente.familia.store'), $this->datosValidos([
                'documento' => '9999999',
                'fecha_nacimiento' => '2026-12-01',
            ]));
        $conInexistente->assertSessionHasErrors('fecha_nacimiento');
        $conInexistente->assertSessionDoesntHaveErrors('documento');
        $erroresConInexistente = $conInexistente->getSession()->get('errors')->toArray();

        $this->assertSame($erroresConInexistente, $erroresConExistente);
    }

    public function test_edita_un_familiar_propio(): void
    {
        $tomas = $this->familiarDe($this->tutor, ['documento' => '7123456']);

        $this->actingAs($this->tutor)
            ->put(route('paciente.familia.update', $tomas), $this->datosValidos(['nombre' => 'Tomás Andrés']))
            ->assertRedirect(route('paciente.familia'))
            ->assertSessionHas('success');

        $this->assertSame('Tomás Andrés', $tomas->fresh()->nombre);
        $this->assertSame($this->tutor->id, $tomas->fresh()->responsable_id);
    }

    public function test_al_editar_el_documento_repetido_tambien_se_rechaza(): void
    {
        $tomas = $this->familiarDe($this->tutor, ['documento' => '7123456']);
        Paciente::factory()->create(['documento' => '6543210', 'tipo_documento' => 'CI']);

        $this->actingAs($this->tutor)
            ->put(route('paciente.familia.update', $tomas), $this->datosValidos(['documento' => '6543210']))
            ->assertSessionHasErrors('documento');

        $this->assertSame('7123456', $tomas->fresh()->documento);
    }

    public function test_no_puede_editar_familiares_ajenos(): void
    {
        $otroTutor = User::factory()->paciente()->create();
        $ajeno = $this->familiarDe($otroTutor, ['nombre' => 'Ajeno']);

        $this->actingAs($this->tutor)
            ->put(route('paciente.familia.update', $ajeno), $this->datosValidos(['documento' => '1111111']))
            ->assertForbidden();

        $this->assertSame('Ajeno', $ajeno->fresh()->nombre);
    }

    public function test_no_puede_editar_su_propio_registro_como_familiar(): void
    {
        $this->actingAs($this->tutor)
            ->put(route('paciente.familia.update', $this->titular), $this->datosValidos(['documento' => '1111111']))
            ->assertForbidden();
    }

    public function test_no_se_puede_quitar_un_familiar(): void
    {
        $tomas = $this->familiarDe($this->tutor);

        $this->actingAs($this->tutor)
            ->delete(route('paciente.familia.update', $tomas))
            ->assertMethodNotAllowed();

        $this->assertNotNull($tomas->fresh());
    }

    public function test_reservar_preselecciona_al_familiar_pedido(): void
    {
        $sofia = $this->familiarDe($this->tutor, ['nombre' => 'Sofía']);

        $respuesta = $this->actingAs($this->tutor)
            ->get(route('paciente.reservar', $this->agendaConHorarios() + ['para' => $sofia->id]))
            ->assertOk();

        $this->assertSame((string) $sofia->id, $this->radioMarcado($respuesta->getContent()));
    }

    public function test_reservar_conserva_al_familiar_al_cambiar_de_paso(): void
    {
        $sofia = $this->familiarDe($this->tutor, ['nombre' => 'Sofía']);

        $this->actingAs($this->tutor)
            ->get(route('paciente.reservar', ['para' => $sofia->id]))
            ->assertOk()
            ->assertSee('name="para" value="'.$sofia->id.'"', false);
    }

    public function test_reservar_ignora_un_familiar_ajeno_y_marca_al_titular(): void
    {
        $this->familiarDe($this->tutor, ['nombre' => 'Sofía']);
        $ajeno = $this->familiarDe(User::factory()->paciente()->create());

        $respuesta = $this->actingAs($this->tutor)
            ->get(route('paciente.reservar', $this->agendaConHorarios() + ['para' => $ajeno->id]))
            ->assertOk();

        $this->assertSame((string) $this->titular->id, $this->radioMarcado($respuesta->getContent()));
    }

    /**
     * Profesional con un servicio y horarios libres el lunes 19/10.
     *
     * @return array{profesional: int, servicio: int, fecha: string}
     */
    private function agendaConHorarios(): array
    {
        $consultorio = Consultorio::factory()->create(['activo' => true]);
        $profesional = Profesional::factory()->create();
        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $profesional->servicios()->attach($servicio->id);

        Disponibilidad::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        return [
            'profesional' => $profesional->id,
            'servicio' => $servicio->id,
            'fecha' => '2026-10-19',
        ];
    }

    private function radioMarcado(string $html): ?string
    {
        preg_match_all('/<input[^>]*name="paciente_id"[^>]*>/s', $html, $radios);

        foreach ($radios[0] as $radio) {
            if (preg_match('/\schecked\b/', $radio) && preg_match('/value="(\d+)"/', $radio, $valor)) {
                return $valor[1];
            }
        }

        return null;
    }
}
