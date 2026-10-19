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
            [
                'especialidad' => 'Odontología general',
                'nombre' => 'Consulta general',
                'duracion_min' => 30,
                'descripcion' => 'Evaluación integral de la salud bucal, diagnóstico y plan de tratamiento personalizado.',
            ],
            [
                'especialidad' => 'Odontología general',
                'nombre' => 'Limpieza dental',
                'duracion_min' => 45,
                'descripcion' => 'Eliminación de placa bacteriana, sarro y manchas superficiales para encías saludables.',
            ],
            [
                'especialidad' => 'Odontología general',
                'nombre' => 'Empaste',
                'duracion_min' => 45,
                'descripcion' => 'Restauración estética con resina compuesta para tratar caries y devolver la anatomía dental.',
            ],
            [
                'especialidad' => 'Odontología general',
                'nombre' => 'Extracción',
                'duracion_min' => 45,
                'descripcion' => 'Procedimiento quirúrgico seguro para la remoción de piezas dentarias no restaurables.',
            ],
            [
                'especialidad' => 'Odontología general',
                'nombre' => 'Blanqueamiento',
                'duracion_min' => 60,
                'descripcion' => 'Aclaramiento dental profesional en consultorio para una sonrisa más luminosa.',
            ],
            [
                'especialidad' => 'Odontopediatría',
                'nombre' => 'Control infantil',
                'duracion_min' => 30,
                'descripcion' => 'Atención odontológica adaptada y preventiva para el cuidado dental de niños y niñas.',
            ],
            [
                'especialidad' => 'Odontopediatría',
                'nombre' => 'Sellantes',
                'duracion_min' => 30,
                'descripcion' => 'Aplicación de resina protectora en fosas y fisuras para prevenir caries en dientes infantiles.',
            ],
            [
                'especialidad' => 'Endodoncia',
                'nombre' => 'Tratamiento de conducto',
                'duracion_min' => 90,
                'descripcion' => 'Tratamiento del tejido pulpar inflamado o infectado para salvar la pieza dental natural.',
            ],
            [
                'especialidad' => 'Ortodoncia',
                'nombre' => 'Consulta de ortodoncia',
                'duracion_min' => 30,
                'descripcion' => 'Diagnóstico y planificación para la corrección de la alineación dental y mordida.',
            ],
            [
                'especialidad' => 'Rehabilitación oral',
                'nombre' => 'Prótesis',
                'duracion_min' => 60,
                'descripcion' => 'Reemplazo de piezas dentarias perdidas mediante prótesis fijas o removibles de alta estética.',
            ],
            [
                'especialidad' => 'Rehabilitación oral',
                'nombre' => 'Implantes',
                'duracion_min' => 90,
                'descripcion' => 'Colocación de implantes de titanio oseointegrados para reponer dientes de forma definitiva.',
            ],
        ];

        DB::table('servicios')->insert(array_map(fn (array $s) => [
            'especialidad_id' => $especialidadId($s['especialidad']),
            'nombre' => $s['nombre'],
            'descripcion' => $s['descripcion'],
            'duracion_min' => $s['duracion_min'],
            'precio' => null,
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $servicios));
    }
}
