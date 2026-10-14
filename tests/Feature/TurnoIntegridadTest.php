<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TurnoIntegridadTest extends TestCase
{
    use RefreshDatabase;

    private int $profesionalId;
    private int $pacienteId;
    private int $servicioId;
    private int $consultorioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        // Ana (PY-1001) ofrece "Consulta general", así que la FK compuesta lo acepta
        $this->profesionalId = DB::table('profesionales')->where('matricula', 'PY-1001')->value('id');
        $this->pacienteId = DB::table('pacientes')->where('nombre', 'Carlos')->value('id');
        $this->servicioId = DB::table('servicios')->where('nombre', 'Consulta general')->value('id');
        $this->consultorioId = DB::table('consultorios')->value('id');
    }

    private function crearTurno(string $inicio, string $fin, string $estado = 'solicitado'): void
    {
        DB::table('turnos')->insert([
            'paciente_id' => $this->pacienteId,
            'profesional_id' => $this->profesionalId,
            'servicio_id' => $this->servicioId,
            'consultorio_id' => $this->consultorioId,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => $estado,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_turnos_solapados_del_mismo_profesional_fallan(): void
    {
        $this->crearTurno('2026-10-20 10:00:00-03', '2026-10-20 10:30:00-03');

        $this->expectException(QueryException::class);

        $this->crearTurno('2026-10-20 10:15:00-03', '2026-10-20 10:45:00-03');
    }

    public function test_turnos_contiguos_pasan(): void
    {
        $this->crearTurno('2026-10-20 10:00:00-03', '2026-10-20 10:30:00-03');
        $this->crearTurno('2026-10-20 10:30:00-03', '2026-10-20 11:00:00-03');

        $this->assertSame(2, DB::table('turnos')->count());
    }

    public function test_turno_cancelado_no_bloquea_el_horario(): void
    {
        $this->crearTurno('2026-10-20 10:00:00-03', '2026-10-20 10:30:00-03', 'cancelado');
        $this->crearTurno('2026-10-20 10:00:00-03', '2026-10-20 10:30:00-03', 'solicitado');

        $this->assertSame(2, DB::table('turnos')->count());
    }
}
