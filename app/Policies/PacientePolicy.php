<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;

class PacientePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [RolUsuario::Funcionario, RolUsuario::Profesional], true);
    }

    public function view(User $user, Paciente $paciente): bool
    {
        if ($user->rol === RolUsuario::Funcionario) {
            return true;
        }

        if ($user->rol === RolUsuario::Profesional && $user->profesional) {
            return $paciente->turnos()->where('profesional_id', $user->profesional->id)->exists();
        }

        return false;
    }

    public function update(User $user, Paciente $paciente): bool
    {
        return $this->view($user, $paciente);
    }

    public function agendarPara(User $user, Paciente $paciente): bool
    {
        if ($user->rol === RolUsuario::Funcionario) {
            return true;
        }

        if ($user->rol === RolUsuario::Profesional && $user->profesional) {
            return ! $paciente->turnos()->exists()
                || $paciente->turnos()->where('profesional_id', $user->profesional->id)->exists();
        }

        return false;
    }
}
