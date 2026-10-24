<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Notificacion;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CargarDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Cargar seeders base para que existan usuarios, profesionales, consultorios y servicios
        $this->seed(DatabaseSeeder::class);
    }

    public function test_se_niega_a_correr_en_produccion_sin_force(): void
    {
        $turnosAntes = Turno::count();

        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('demo:cargar')
            ->expectsOutputToContain('producción')
            ->assertExitCode(1);

        $this->assertSame($turnosAntes, Turno::count());

        $this->artisan('demo:cargar', ['--force' => true])
            ->assertSuccessful();

        $this->assertGreaterThan($turnosAntes, Turno::count());
    }

    public function test_carga_datos_demo_con_turnos_solicitudes_y_sin_notificaciones(): void
    {
        $this->artisan('demo:cargar')
            ->assertSuccessful();

        // 1. Hay turnos hoy (o día hábil si es fin de semana)
        $tz = config('app.timezone', 'America/Asuncion');
        $hoy = now($tz);
        $diaPrincipal = $hoy->isWeekend() ? $hoy->copy()->next(Carbon::MONDAY) : $hoy;

        $turnosDiaPrincipal = Turno::whereDate('inicio', $diaPrincipal->toDateString())->get();
        $this->assertTrue($turnosDiaPrincipal->isNotEmpty(), 'No se encontraron turnos para el día principal.');

        // 2. Solicitudes pendientes > 0
        $solicitudesPendientes = Turno::where('estado', EstadoTurno::Solicitado)
            ->where('inicio', '>=', now($tz))
            ->count();
        $this->assertGreaterThan(0, $solicitudesPendientes, 'Debe haber solicitudes pendientes en el futuro.');

        // 3. Ningún turno solapado por profesional
        $turnosPorProfesional = Turno::where('estado', '!=', EstadoTurno::Cancelado)
            ->orderBy('profesional_id')
            ->orderBy('inicio')
            ->get()
            ->groupBy('profesional_id');

        foreach ($turnosPorProfesional as $profesionalId => $turnos) {
            $anterior = null;
            foreach ($turnos as $turno) {
                if ($anterior !== null) {
                    $this->assertTrue(
                        $anterior->fin->lte($turno->inicio),
                        "Solapamiento detectado para profesional {$profesionalId}: turno {$anterior->id} ({$anterior->inicio} - {$anterior->fin}) solapa con {$turno->id} ({$turno->inicio} - {$turno->fin})"
                    );
                }
                $anterior = $turno;
            }
        }

        // 4. Ninguna notificación creada
        $this->assertSame(0, Notificacion::count(), 'No se debieron crear notificaciones.');

        // 5. paciente@ y tutor@ tienen al menos un turno próximo
        $pacienteUser = User::where('email', 'paciente@glowdental.test')->firstOrFail();
        $tutorUser = User::where('email', 'tutor@glowdental.test')->firstOrFail();

        $turnosProximosPaciente = Turno::where('paciente_id', $pacienteUser->paciente?->id)
            ->where('inicio', '>=', now($tz))
            ->whereIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado])
            ->count();
        $this->assertGreaterThan(0, $turnosProximosPaciente, 'paciente@ debe tener al menos un turno próximo.');

        $idsTutorYFamilia = $tutorUser->pacientesIds();
        $turnosProximosTutor = Turno::whereIn('paciente_id', $idsTutorYFamilia)
            ->where('inicio', '>=', now($tz))
            ->whereIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado])
            ->count();
        $this->assertGreaterThan(0, $turnosProximosTutor, 'tutor@ o sus familiares deben tener al menos un turno próximo.');
    }

    public function test_opcion_fresh_reinicia_y_carga_datos(): void
    {
        $this->artisan('demo:cargar', ['--fresh' => true])
            ->assertSuccessful();

        $this->assertGreaterThan(50, Turno::count());
        $this->assertSame(0, Notificacion::count());
    }

    public function test_miercoles_a_las_07_00_sin_atendidos_hoy_y_futuros_correctos(): void
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $ahora = Carbon::parse('2026-10-21 07:00:00', $tz); // Miércoles 07:00
        Carbon::setTestNow($ahora);

        $this->artisan('demo:cargar')
            ->assertSuccessful();

        // Hoy no debe tener turnos Atendido ni Ausente porque aún no empezó la jornada
        $pasadosHoy = Turno::whereDate('inicio', '2026-10-21')
            ->whereIn('estado', [EstadoTurno::Atendido, EstadoTurno::Ausente])
            ->count();
        $this->assertSame(0, $pasadosHoy, 'A las 07:00 no deben existir turnos atendidos ni ausentes hoy.');

        // Debe haber futuros hoy
        $futurosHoy = Turno::whereDate('inicio', '2026-10-21')
            ->whereIn('estado', [EstadoTurno::Confirmado, EstadoTurno::Solicitado])
            ->count();
        $this->assertGreaterThan(0, $futurosHoy, 'A las 07:00 debe haber turnos confirmados/solicitados hoy.');

        $this->verificarInvariantesDeTurnos($ahora);
    }

    public function test_miercoles_a_las_15_00_hay_atendidos_y_ningun_confirmado_pasado(): void
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $ahora = Carbon::parse('2026-10-21 15:00:00', $tz); // Miércoles 15:00
        Carbon::setTestNow($ahora);

        $this->artisan('demo:cargar')
            ->assertSuccessful();

        // Debe haber turnos atendidos hoy
        $atendidosHoy = Turno::whereDate('inicio', '2026-10-21')
            ->where('estado', EstadoTurno::Atendido)
            ->count();
        $this->assertGreaterThan(0, $atendidosHoy, 'A las 15:00 debe haber turnos atendidos hoy.');

        // Ningún Confirmado de hoy con inicio pasado
        $confirmadosPasadosHoy = Turno::whereDate('inicio', '2026-10-21')
            ->where('estado', EstadoTurno::Confirmado)
            ->where('inicio', '<', $ahora)
            ->count();
        $this->assertSame(0, $confirmadosPasadosHoy, 'A las 15:00 no debe haber turnos confirmados pasados hoy.');

        $this->verificarInvariantesDeTurnos($ahora);
    }

    public function test_sabado_ningun_atendido_con_inicio_futuro(): void
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $ahora = Carbon::parse('2026-10-24 10:00:00', $tz); // Sábado 10:00
        Carbon::setTestNow($ahora);

        $this->artisan('demo:cargar')
            ->assertSuccessful();

        // Ningún turno en sábado
        $turnosSabado = Turno::whereDate('inicio', '2026-10-24')->count();
        $this->assertSame(0, $turnosSabado, 'No debe haber turnos en día sábado sin disponibilidad.');

        $this->verificarInvariantesDeTurnos($ahora);
    }

    private function verificarInvariantesDeTurnos(Carbon $ahora): void
    {
        $tz = config('app.timezone', 'America/Asuncion');

        // 1. Ningún Atendido o Ausente en el futuro
        $pasadosFuturos = Turno::whereIn('estado', [EstadoTurno::Atendido, EstadoTurno::Ausente])
            ->where('inicio', '>', $ahora)
            ->count();
        $this->assertSame(0, $pasadosFuturos, 'No debe haber turnos Atendidos o Ausentes con inicio > now.');

        // 2. Ningún Confirmado o Solicitado en el pasado
        $futurosPasados = Turno::whereIn('estado', [EstadoTurno::Confirmado, EstadoTurno::Solicitado])
            ->where('inicio', '<', $ahora)
            ->count();
        $this->assertSame(0, $futurosPasados, 'No debe haber turnos Confirmados o Solicitados con inicio < now.');

        // 3. Ningún turno fuera de la disponibilidad del profesional
        $turnos = Turno::with(['profesional.disponibilidades'])->get();
        foreach ($turnos as $turno) {
            $inicio = $turno->inicio->copy()->setTimezone($tz);
            $fin = $turno->fin->copy()->setTimezone($tz);
            $diaSemana = $inicio->dayOfWeekIso;

            $calzaEnDisponibilidad = $turno->profesional->disponibilidades
                ->where('dia_semana', $diaSemana)
                ->contains(function ($disp) use ($inicio, $fin) {
                    $hInicio = substr($disp->hora_inicio, 0, 5);
                    $hFin = substr($disp->hora_fin, 0, 5);
                    $tInicio = $inicio->format('H:i');
                    $tFin = $fin->format('H:i');

                    return $tInicio >= $hInicio && $tFin <= $hFin;
                });

            $this->assertTrue(
                $calzaEnDisponibilidad,
                "Turno {$turno->id} ({$inicio->toDateTimeString()} - {$fin->toDateTimeString()}) para profesional {$turno->profesional_id} cae fuera de su disponibilidad."
            );
        }
    }
}
