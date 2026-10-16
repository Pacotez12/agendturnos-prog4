<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destinatario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('canal', 20)->default('correo');
            $table->string('estado', 20)->default('pendiente');
            $table->timestampTz('enviado_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });

        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_tipo_check CHECK (tipo IN ('confirmacion', 'recordatorio', 'cancelacion', 'reprogramacion'))");
        DB::statement("ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_estado_check CHECK (estado IN ('pendiente', 'enviada', 'fallida'))");
        DB::statement('CREATE UNIQUE INDEX notificaciones_turno_tipo_destinatario_unique ON notificaciones (turno_id, tipo, destinatario_id) WHERE turno_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
