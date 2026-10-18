<?php

namespace App\Enums;

enum EstadoNotificacion: string
{
    case Pendiente = 'pendiente';
    case Enviada = 'enviada';
    case Fallida = 'fallida';
}
