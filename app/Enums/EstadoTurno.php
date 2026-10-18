<?php

namespace App\Enums;

enum EstadoTurno: string
{
    case Solicitado = 'solicitado';
    case Confirmado = 'confirmado';
    case Atendido = 'atendido';
    case Cancelado = 'cancelado';
    case Ausente = 'ausente';
}
