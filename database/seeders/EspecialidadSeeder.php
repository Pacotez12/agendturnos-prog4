<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        DB::table('especialidades')->insert([
            ['nombre' => 'Odontología general', 'activa' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Ortodoncia', 'activa' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Endodoncia', 'activa' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }
}
