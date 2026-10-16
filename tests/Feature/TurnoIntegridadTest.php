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

    private int $profesionalAnaId;

    private int $profesionalLuciaId;

    private int $pacienteId;

    private int $servicioConsultaId;

    private int $servicioLimpiezaId;

    private int $consultorioId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->profesionalAnaId = DB::table('profesionales')->where('matricula', 'PY-1001')->value('id');
        $this->profesionalLuciaId = DB::table('profesionales')->where('matricula', 'PY-1002')->value('id');
        $this->pacienteId = DB::table('pacientes')->where('nombre', 'Carlos')->value('id');
        $this->servicioConsultaId = DB::table('servicios')->where('nombre', 'Consulta general')->value('id');
        $this->servicioLimpiezaId = DB::table('servicios')->where('nombre', 'Limpieza dental')->value('id');
        $this->consultorioId = DB::table('consultorios')->value('id');
    }

    private function crearTurno(
        string $inicio,
        string $fin,
        string $estado = 'solicitado',
        ?int $profesionalId = null,
        ?int $servicioId = null,
    ): void {
        DB::table('turnos')->insert([
            'paciente_id' => $this->pacienteId,
            'profesional_id' => $profesionalId ?? $this->profesionalAnaId,
            'servicio_id' => $servicioId ?? $this->servicioConsultaId,
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

    public function test_estado_invalido_falla(): void
    {
        $this->expectException(QueryException::class);

        $this->crearTurno('2026-10-21 10:00:00-03', '2026-10-21 10:30:00-03', 'inventado');
    }

    public function test_servicio_que_el_profesional_no_ofrece_falla(): void
    {
        // Lucía no ofrece "Limpieza dental"
        $this->expectException(QueryException::class);

        $this->crearTurno(
            '2026-10-21 10:00:00-03',
            '2026-10-21 10:45:00-03',
            'solicitado',
            $this->profesionalLuciaId,
            $this->servicioLimpiezaId,
        );
    }

    public function test_documento_duplicado_falla(): void
    {
        // Carlos (CI 4123456) ya existe por el seeder
        $this->expectException(QueryException::class);

        DB::table('pacientes')->insert([
            'user_id' => null,
            'responsable_id' => null,
            'nombre' => 'Duplicada',
            'apellido' => 'Prueba',
            'tipo_documento' => 'CI',
            'documento' => '4123456',
            'telefono' => null,
            'fecha_nacimiento' => '1990-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_historial_sin_usuario_pasa(): void
    {
        // Cambios automáticos (p. ej. el scheduler marcando ausente) no tienen usuario
        $this->crearTurno('2026-10-22 10:00:00-03', '2026-10-22 10:30:00-03');

        DB::table('historial_turnos')->insert([
            'turno_id' => DB::table('turnos')->value('id'),
            'estado_anterior' => 'confirmado',
            'estado_nuevo' => 'ausente',
            'usuario_id' => null,
        ]);

        $this->assertSame(1, DB::table('historial_turnos')->whereNull('usuario_id')->count());
    }

    public function test_precio_en_guaranies_se_guarda_como_entero(): void
    {
        DB::table('servicios')->where('id', $this->servicioConsultaId)->update(['precio' => 150000]);

        $this->assertSame(150000, DB::table('servicios')->where('id', $this->servicioConsultaId)->value('precio'));
    }

    public function test_precio_negativo_falla(): void
    {
        $this->expectException(QueryException::class);

        DB::table('servicios')->where('id', $this->servicioConsultaId)->update(['precio' => -1]);
    }
}
