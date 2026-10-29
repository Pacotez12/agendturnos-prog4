<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Jobs\EnviarNotificacion;
use App\Models\Consultorio;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HorarioSilenciosoTest extends TestCase
{
    use RefreshDatabase;

    private User $pacienteUser;

    private User $profesionalUser;

    private Turno $turno;

    private NotificacionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(NotificacionService::class);

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'paciente@test.com']);
        $paciente = Paciente::factory()->create(['user_id' => $this->pacienteUser->id]);

        $this->profesionalUser = User::factory()->profesional()->create();
        $profesional = Profesional::factory()->create(['user_id' => $this->profesionalUser->id]);

        $consultorio = Consultorio::factory()->create();
        $servicio = Servicio::factory()->create();
        $profesional->servicios()->attach($servicio->id);

        $this->turno = Turno::create([
            'paciente_id' => $paciente->id,
            'profesional_id' => $profesional->id,
            'servicio_id' => $servicio->id,
            'consultorio_id' => $consultorio->id,
            'inicio' => '2026-10-25 10:00:00',
            'fin' => '2026-10-25 10:30:00',
            'estado' => EstadoTurno::Confirmado,
        ]);
    }

    public function test_22_30_despacha_con_delay_a_las_08_00_del_dia_siguiente(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-20 22:30:00', config('app.timezone')));

        $this->servicio->programar($this->turno, 'confirmacion');

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            $esperado = Carbon::parse('2026-10-21 08:00:00', config('app.timezone'));

            return $job->delay instanceof \DateTimeInterface
                && $job->delay->format('Y-m-d H:i:s') === $esperado->format('Y-m-d H:i:s');
        });
    }

    public function test_03_00_despacha_con_delay_a_las_08_00_del_mismo_dia(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-21 03:00:00', config('app.timezone')));

        $this->servicio->programar($this->turno, 'confirmacion');

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            $esperado = Carbon::parse('2026-10-21 08:00:00', config('app.timezone'));

            return $job->delay instanceof \DateTimeInterface
                && $job->delay->format('Y-m-d H:i:s') === $esperado->format('Y-m-d H:i:s');
        });
    }

    public function test_08_00_y_20_59_despachan_sin_delay(): void
    {
        Queue::fake();

        // 08:00 -> sin delay
        Carbon::setTestNow(Carbon::parse('2026-10-21 08:00:00', config('app.timezone')));
        $this->servicio->programar($this->turno, 'confirmacion');

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            return $job->delay === null;
        });

        // 20:59 -> sin delay
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-21 20:59:00', config('app.timezone')));
        $this->servicio->programar($this->turno, 'recordatorio');

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            return $job->delay === null;
        });
    }

    public function test_reenvio_manual_a_las_23_00_despacha_sin_delay(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-21 23:00:00', config('app.timezone')));

        $this->servicio->reenviarRecordatorio($this->turno, $this->profesionalUser);

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            return $job->delay === null;
        });
    }

    public function test_rango_silencioso_configurable(): void
    {
        Queue::fake();

        config([
            'agenda.silencio_desde' => '22:00',
            'agenda.silencio_hasta' => '07:00',
        ]);

        // A las 21:30 no está en silencio
        Carbon::setTestNow(Carbon::parse('2026-10-21 21:30:00', config('app.timezone')));
        $this->servicio->programar($this->turno, 'confirmacion');

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            return $job->delay === null;
        });

        // A las 22:30 está en silencio -> delay a las 07:00 de mañana
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-21 22:30:00', config('app.timezone')));
        $this->servicio->programar($this->turno, 'confirmacion');

        Queue::assertPushed(EnviarNotificacion::class, function ($job) {
            $esperado = Carbon::parse('2026-10-22 07:00:00', config('app.timezone'));

            return $job->delay instanceof \DateTimeInterface
                && $job->delay->format('Y-m-d H:i:s') === $esperado->format('Y-m-d H:i:s');
        });
    }

    public function test_metodo_momento_de_envio_calcula_correctamente(): void
    {
        $tz = config('app.timezone');

        // Fuera de horario silencioso (default 21:00 a 08:00)
        $this->assertNull($this->servicio->momentoDeEnvio(Carbon::parse('2026-10-21 08:00:00', $tz)));
        $this->assertNull($this->servicio->momentoDeEnvio(Carbon::parse('2026-10-21 15:30:00', $tz)));
        $this->assertNull($this->servicio->momentoDeEnvio(Carbon::parse('2026-10-21 20:59:59', $tz)));

        // Dentro de horario silencioso: noche
        $noche = Carbon::parse('2026-10-21 22:30:00', $tz);
        $momentoNoche = $this->servicio->momentoDeEnvio($noche);
        $this->assertNotNull($momentoNoche);
        $this->assertSame('2026-10-22 08:00:00', $momentoNoche->format('Y-m-d H:i:s'));

        // Dentro de horario silencioso: madrugada
        $madrugada = Carbon::parse('2026-10-22 03:00:00', $tz);
        $momentoMadrugada = $this->servicio->momentoDeEnvio($madrugada);
        $this->assertNotNull($momentoMadrugada);
        $this->assertSame('2026-10-22 08:00:00', $momentoMadrugada->format('Y-m-d H:i:s'));
    }

    public function test_recordatorio_en_horario_silencioso_no_se_programa_si_demora_cae_en_o_despues_de_inicio(): void
    {
        Queue::fake();

        // Turno a las 07:30 del día siguiente
        $this->turno->update([
            'inicio' => '2026-10-21 07:30:00',
            'fin' => '2026-10-21 08:00:00',
        ]);

        // A las 22:30 de la noche anterior (horario silencioso: demora hasta las 08:00 de mañana >= 07:30)
        Carbon::setTestNow(Carbon::parse('2026-10-20 22:30:00', config('app.timezone')));

        $resultado = $this->servicio->programar($this->turno, 'recordatorio');

        $this->assertNull($resultado);
        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('notificaciones', [
            'turno_id' => $this->turno->id,
            'tipo' => 'recordatorio',
        ]);

        // En cambio, una confirmación en el mismo escenario SÍ se programa con delay
        $confirmacion = $this->servicio->programar($this->turno, 'confirmacion');
        $this->assertNotNull($confirmacion);
        Queue::assertPushed(EnviarNotificacion::class);
    }
}
