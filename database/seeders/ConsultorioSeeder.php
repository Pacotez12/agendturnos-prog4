<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsultorioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('consultorios')->insert([
            'nombre' => 'Glow Dental Protect - Central',
            'direccion' => 'Asunción, Paraguay',
            'telefono' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
