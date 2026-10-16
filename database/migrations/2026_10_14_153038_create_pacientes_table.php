<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('tipo_documento', 20);
            $table->string('documento', 30);
            $table->string('telefono', 30)->nullable();
            $table->date('fecha_nacimiento');
            $table->timestamps();

            $table->unique(['tipo_documento', 'documento']);
        });

        DB::statement("ALTER TABLE pacientes ADD CONSTRAINT pacientes_tipo_documento_check CHECK (tipo_documento IN ('CI', 'pasaporte'))");
        DB::statement('ALTER TABLE pacientes ADD CONSTRAINT pacientes_fecha_nacimiento_check CHECK (fecha_nacimiento <= CURRENT_DATE)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
