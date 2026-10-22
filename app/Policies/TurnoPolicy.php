<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Turno;
use App\Models\User;

class TurnoPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [RolUsuario::Funcionario, RolUsuario::Profesional], true);
    }

    public function view(User $user, Turno $turno): bool
    {
        if ($user->rol === RolUsuario::Funcionario) {
            return true;
        }

        if ($user->rol === RolUsuario::Profesional) {
            return $user->profesional && $turno->profesional_id === $user->profesional->id;
        }

        return false;
    }

    public function operate(User $user, Turno $turno): bool
    {
        return $this->view($user, $turno);
    }
}
