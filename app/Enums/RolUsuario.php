<?php

namespace App\Enums;

enum RolUsuario: string
{
    case Paciente = 'paciente';
    case Profesional = 'profesional';
    case Funcionario = 'funcionario';
}
