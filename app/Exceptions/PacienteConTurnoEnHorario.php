<?php

namespace App\Exceptions;

use App\Models\Paciente;
use Illuminate\Database\QueryException;
use RuntimeException;
use Throwable;

class PacienteConTurnoEnHorario extends RuntimeException
{
    public const RESTRICCION = 'turnos_paciente_sin_solapamiento';

    public static function para(Paciente $paciente): self
    {
        return new self("{$paciente->nombre} {$paciente->apellido} ya tiene otro turno en ese horario. Elegí otro.");
    }

    public static function siSolapamiento(Throwable $e, ?Paciente $paciente = null): ?self
    {
        if ($e instanceof QueryException && str_contains($e->getMessage(), self::RESTRICCION)) {
            return $paciente
                ? new self(self::para($paciente)->getMessage(), 0, $e)
                : new self('El paciente ya tiene otro turno en ese horario. Elegí otro.', 0, $e);
        }

        return null;
    }
}
