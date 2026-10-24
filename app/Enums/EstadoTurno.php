<?php

namespace App\Enums;

enum EstadoTurno: string
{
    case Solicitado = 'solicitado';
    case Confirmado = 'confirmado';
    case Atendido = 'atendido';
    case Cancelado = 'cancelado';
    case Ausente = 'ausente';

    public function puedePasarA(self $nuevo): bool
    {
        return match ($this) {
            self::Solicitado => in_array($nuevo, [self::Confirmado, self::Cancelado], true),
            self::Confirmado => in_array($nuevo, [self::Atendido, self::Cancelado, self::Ausente], true),
            default => false,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Solicitado => 'Solicitado',
            self::Confirmado => 'Confirmado',
            self::Atendido => 'Atendido',
            self::Cancelado => 'Cancelado',
            self::Ausente => 'Ausente',
        };
    }
}
