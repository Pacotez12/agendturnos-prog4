<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicioSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $especialidadId = fn (string $nombre) => DB::table('especialidades')
            ->where('nombre', $nombre)
            ->value('id');

        $servicios = [
            ['especialidad' => 'Odontología general', 'nombre' => 'Consulta general', 'duracion_min' => 30],
            ['especialidad' => 'Odontología general', 'nombre' => 'Limpieza dental', 'duracion_min' => 45],
            ['especialidad' => 'Odontología general', 'nombre' => 'Empaste', 'duracion_min' => 45],
            ['especialidad' => 'Odontología general', 'nombre' => 'Extracción', 'duracion_min' => 45],
            ['especialidad' => 'Odontología general', 'nombre' => 'Blanqueamiento', 'duracion_min' => 60],
            ['especialidad' => 'Endodoncia', 'nombre' => 'Tratamiento de conducto', 'duracion_min' => 90],
            ['especialidad' => 'Ortodoncia', 'nombre' => 'Consulta de ortodoncia', 'duracion_min' => 30],
        ];

        DB::table('servicios')->insert(array_map(fn (array $s) => [
            'especialidad_id' => $especialidadId($s['especialidad']),
            'nombre' => $s['nombre'],
            'duracion_min' => $s['duracion_min'],
            'precio' => null,
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $servicios));
    }
}
