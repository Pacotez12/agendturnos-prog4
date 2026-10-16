<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConsultorioSeeder::class,
            EspecialidadSeeder::class,
            ServicioSeeder::class,
            UserSeeder::class,
            ProfesionalSeeder::class,
            PacienteSeeder::class,
        ]);
    }
}
