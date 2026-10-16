<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $clave = Hash::make('password');

        User::create([
            'email' => 'funcionario@glowdental.test',
            'password' => $clave,
            'rol' => 'funcionario',
        ]);

        User::create([
            'email' => 'profesional1@glowdental.test',
            'password' => $clave,
            'rol' => 'profesional',
        ]);

        User::create([
            'email' => 'profesional2@glowdental.test',
            'password' => $clave,
            'rol' => 'profesional',
        ]);

        User::create([
            'email' => 'tutor@glowdental.test',
            'password' => $clave,
            'rol' => 'paciente',
        ]);

        User::create([
            'email' => 'paciente@glowdental.test',
            'password' => $clave,
            'rol' => 'paciente',
        ]);
    }
}
