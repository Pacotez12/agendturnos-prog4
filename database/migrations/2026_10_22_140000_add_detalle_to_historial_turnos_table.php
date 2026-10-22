<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_turnos', function (Blueprint $table) {
            $table->string('detalle', 255)->nullable()->after('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::table('historial_turnos', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });
    }
};
