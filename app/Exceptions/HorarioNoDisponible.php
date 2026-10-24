<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use RuntimeException;
use Throwable;

class HorarioNoDisponible extends RuntimeException
{
    public static function siSolapamiento(Throwable $e): ?self
    {
        if ($e instanceof QueryException && (
            $e->getCode() === '23P01'
            || str_contains($e->getMessage(), '23P01')
            || str_contains($e->getMessage(), 'turnos_sin_solapamiento')
        )) {
            return new self('El horario seleccionado no está disponible.', 0, $e);
        }

        return null;
    }
}
