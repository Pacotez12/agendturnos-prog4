<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Un paciente no puede tener dos turnos que se superpongan, aunque sean con
     * profesionales distintas. Misma idea que turnos_sin_solapamiento, pero por paciente.
     */
    public function up(): void
    {
        $choques = DB::select(<<<'SQL'
            SELECT a.id AS turno_a, b.id AS turno_b, a.paciente_id
            FROM turnos a
            JOIN turnos b ON a.paciente_id = b.paciente_id AND a.id < b.id
            WHERE a.estado <> 'cancelado' AND b.estado <> 'cancelado'
              AND tstzrange(a.inicio, a.fin, '[)') && tstzrange(b.inicio, b.fin, '[)')
            ORDER BY a.id
            LIMIT 10
        SQL);

        if ($choques !== []) {
            $detalle = collect($choques)
                ->map(fn ($c) => "paciente {$c->paciente_id}: turnos {$c->turno_a} y {$c->turno_b}")
                ->implode('; ');

            throw new RuntimeException(
                "Hay pacientes con turnos superpuestos ({$detalle}). Cancelá o reprogramá uno de cada par y volvé a migrar."
            );
        }

        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_paciente_sin_solapamiento EXCLUDE USING gist (paciente_id WITH =, tstzrange(inicio, fin, '[)') WITH &&) WHERE (estado <> 'cancelado')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE turnos DROP CONSTRAINT IF EXISTS turnos_paciente_sin_solapamiento');
    }
};
