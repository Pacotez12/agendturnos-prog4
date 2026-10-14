<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->restrictOnDelete();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE historial_turnos ADD CONSTRAINT historial_estado_anterior_check CHECK (estado_anterior IS NULL OR estado_anterior IN ('solicitado', 'confirmado', 'cancelado', 'atendido', 'ausente'))");
        DB::statement("ALTER TABLE historial_turnos ADD CONSTRAINT historial_estado_nuevo_check CHECK (estado_nuevo IN ('solicitado', 'confirmado', 'cancelado', 'atendido', 'ausente'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_turnos');
    }
};
