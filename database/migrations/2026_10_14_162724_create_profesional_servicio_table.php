<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesional_servicio', function (Blueprint $table) {
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['profesional_id', 'servicio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesional_servicio');
    }
};
