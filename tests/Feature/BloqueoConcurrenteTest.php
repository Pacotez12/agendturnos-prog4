<?php

namespace Tests\Feature;

use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BloqueoConcurrenteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_p08_bloqueo_espera_reserva_concurrente_y_advierte_sin_insertar(): void
    {
        $this->assertStringStartsWith('agendturnos_test', DB::connection()->getDatabaseName());
        $profesional = Profesional::factory()->create();
        $servicio = Servicio::factory()->create(['duracion_min' => 30]);
        $profesional->servicios()->attach($servicio);
        $funcionario = User::factory()->funcionario()->create();
        $paciente = Paciente::factory()->create();
        $inicio = now()->addDays(2)->setTime(9, 0);
        Disponibilidad::factory()->create([
            'profesional_id' => $profesional->id, 'consultorio_id' => Consultorio::factory()->create()->id,
            'dia_semana' => $inicio->dayOfWeekIso, 'hora_inicio' => '08:00', 'hora_fin' => '12:00',
        ]);
        $nombreConexion = 'agenda_bloqueo_'.bin2hex(random_bytes(6));
        $proceso = new Process([PHP_BINARY, base_path('tests/Fixtures/crear-bloqueo-concurrente.php')], base_path(), [
            'APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(),
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'LOG_CHANNEL' => 'null',
            'AGENDA_TEST_CONNECTION' => $nombreConexion, 'AGENDA_TEST_USER' => (string) $funcionario->id,
            'AGENDA_TEST_PAYLOAD' => json_encode([
                'profesional_id' => $profesional->id,
                'desde' => $inicio->copy()->subHour()->format('Y-m-d H:i'),
                'hasta' => $inicio->copy()->addHour()->format('Y-m-d H:i'),
            ]),
        ]);
        $proceso->setTimeout(15);

        DB::beginTransaction();
        try {
            app(ReservaService::class)->agendar($profesional, $servicio, $paciente, $inicio, $funcionario);
            $proceso->start();
            $limite = microtime(true) + 10;
            do {
                DB::select('select pg_stat_clear_snapshot()');
                $esperando = DB::selectOne("select count(*) as cantidad from pg_stat_activity where application_name = ? and wait_event_type = 'Lock'", [$nombreConexion]);
                if ((int) $esperando->cantidad > 0) {
                    break;
                }
                if (! $proceso->isRunning()) {
                    $this->fail($proceso->getErrorOutput().$proceso->getOutput());
                }
                usleep(10000);
            } while (microtime(true) < $limite);
            $this->assertGreaterThan(0, (int) $esperando->cantidad, 'El segundo proceso debe esperar a que termine la reserva.');
            DB::commit();
            $proceso->wait();
            $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput().$proceso->getOutput());
            $salida = trim($proceso->getOutput());
            $this->assertJson($salida, $salida.$proceso->getErrorOutput());
            $respuesta = json_decode($salida, true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(302, $respuesta['status']);
            $this->assertTrue($respuesta['conflicto'], 'Debe avisar sobre el turno que confirmó la otra conexión.');
            $this->assertDatabaseCount('bloqueos', 0);
            $this->assertDatabaseCount('turnos', 1);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $proceso->stop();
        }
    }
}
