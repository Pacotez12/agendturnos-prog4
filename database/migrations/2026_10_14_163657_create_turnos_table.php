<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->restrictOnDelete();
            $table->foreignId('consultorio_id')->constrained('consultorios')->restrictOnDelete();
            $table->timestampTz('inicio');
            $table->timestampTz('fin');
            $table->string('estado', 20);
            $table->text('observaciones')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->timestamps();

            $table->foreign(['profesional_id', 'servicio_id'])
                ->references(['profesional_id', 'servicio_id'])
                ->on('profesional_servicio')
                ->restrictOnDelete();

            $table->index(['profesional_id', 'inicio']);
            $table->index(['paciente_id', 'inicio']);
        });

        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_estado_check CHECK (estado IN ('solicitado', 'confirmado', 'cancelado', 'atendido', 'ausente'))");
        DB::statement('ALTER TABLE turnos ADD CONSTRAINT turnos_fin_check CHECK (fin > inicio)');
        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_sin_solapamiento EXCLUDE USING gist (profesional_id WITH =, tstzrange(inicio, fin, '[)') WITH &&) WHERE (estado <> 'cancelado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
