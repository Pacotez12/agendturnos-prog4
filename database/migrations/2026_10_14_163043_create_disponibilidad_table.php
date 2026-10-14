<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            $table->foreignId('consultorio_id')->constrained('consultorios')->restrictOnDelete();
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE disponibilidad ADD CONSTRAINT disponibilidad_dia_semana_check CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE disponibilidad ADD CONSTRAINT disponibilidad_horas_check CHECK (hora_fin > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidad');
    }
};
