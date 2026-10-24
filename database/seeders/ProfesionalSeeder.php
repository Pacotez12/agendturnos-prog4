<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfesionalSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $usuarioId = fn (string $email) => DB::table('users')->where('email', $email)->value('id');
        $servicioId = fn (string $nombre) => DB::table('servicios')->where('nombre', $nombre)->value('id');
        $consultorioId = DB::table('consultorios')->where('nombre', 'Glow Dental Protect - Central')->value('id');

        $profesionales = [
            [
                'email' => 'profesional1@glowdental.test',
                'nombre' => 'Ana',
                'apellido' => 'González',
                'matricula' => '1234',
                'orden' => 2,
                'servicios' => [
                    'Consulta general',
                    'Limpieza dental',
                    'Empaste',
                    'Extracción',
                    'Control infantil',
                    'Sellantes',
                ],
            ],
            [
                'email' => 'profesional2@glowdental.test',
                'nombre' => 'Gloria',
                'apellido' => 'Godoy',
                'matricula' => '1824',
                'orden' => 1,
                'servicios' => [
                    'Consulta general',
                    'Blanqueamiento',
                    'Tratamiento de conducto',
                    'Consulta de ortodoncia',
                    'Prótesis',
                    'Implantes',
                ],
            ],
        ];

        foreach ($profesionales as $p) {
            $profesionalId = DB::table('profesionales')->insertGetId([
                'user_id' => $usuarioId($p['email']),
                'nombre' => $p['nombre'],
                'apellido' => $p['apellido'],
                'matricula' => $p['matricula'],
                'orden' => $p['orden'],
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('profesional_servicio')->insert(array_map(fn (string $nombre) => [
                'profesional_id' => $profesionalId,
                'servicio_id' => $servicioId($nombre),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], $p['servicios']));

            // Lunes a viernes, 08:00 a 12:00 y 14:00 a 18:00 (dia_semana ISO: 1 = lunes)
            $franjas = [['08:00', '12:00'], ['14:00', '18:00']];
            $disponibilidad = [];

            foreach (range(1, 5) as $dia) {
                foreach ($franjas as [$inicio, $fin]) {
                    $disponibilidad[] = [
                        'profesional_id' => $profesionalId,
                        'consultorio_id' => $consultorioId,
                        'dia_semana' => $dia,
                        'hora_inicio' => $inicio,
                        'hora_fin' => $fin,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }
            }

            DB::table('disponibilidad')->insert($disponibilidad);
        }
    }
}
