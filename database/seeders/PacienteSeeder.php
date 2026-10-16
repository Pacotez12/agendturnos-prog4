<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $usuarioId = fn (string $email) => DB::table('users')->where('email', $email)->value('id');

        $tutorId = $usuarioId('tutor@glowdental.test');
        $pacienteUserId = $usuarioId('paciente@glowdental.test');

        DB::table('pacientes')->insert([
            [
                'user_id' => $tutorId,
                'responsable_id' => null,
                'nombre' => 'Carlos',
                'apellido' => 'Gómez',
                'tipo_documento' => 'CI',
                'documento' => '4123456',
                'telefono' => '0981 111 111',
                'fecha_nacimiento' => '1985-03-14',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'user_id' => $pacienteUserId,
                'responsable_id' => null,
                'nombre' => 'María',
                'apellido' => 'Ortiz',
                'tipo_documento' => 'CI',
                'documento' => '5234567',
                'telefono' => '0982 222 222',
                'fecha_nacimiento' => '1992-07-02',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'user_id' => null,
                'responsable_id' => $tutorId,
                'nombre' => 'Sofía',
                'apellido' => 'Gómez',
                'tipo_documento' => 'CI',
                'documento' => '6345678',
                'telefono' => null,
                'fecha_nacimiento' => '2015-11-20',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);
    }
}
