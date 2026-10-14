<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            $table->foreignId('consultorio_id')->nullable()->constrained('consultorios')->restrictOnDelete();
            $table->timestampTz('inicio');
            $table->timestampTz('fin');
            $table->string('motivo', 255)->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE bloqueos ADD CONSTRAINT bloqueos_fin_check CHECK (fin > inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};
