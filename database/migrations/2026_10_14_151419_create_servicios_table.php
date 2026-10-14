<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->smallInteger('duracion_min');
            $table->decimal('precio', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE servicios ADD CONSTRAINT servicios_duracion_check CHECK (duracion_min > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};

