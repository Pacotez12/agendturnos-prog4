<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistroPacienteService
{
    public function registrar(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'email' => $data['email'],
                'password' => $data['password'], // El cast 'hashed' en el modelo User se encarga de hashearla
                'rol' => 'paciente',
                'privacidad_aceptada_at' => now(),
            ]);

            Paciente::create([
                'user_id' => $user->id,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'tipo_documento' => $data['tipo_documento'],
                'documento' => $data['documento'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'telefono' => $data['telefono'] ?? null,
            ]);

            return $user;
        });
    }
}
