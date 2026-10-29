<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Models\Profesional;
use Illuminate\Support\Facades\DB;

class ProfesionalService
{
    public function baja(Profesional $profesional): int
    {
        return DB::transaction(function () use ($profesional) {
            $profesional = Profesional::whereKey($profesional->id)->lockForUpdate()->firstOrFail();
            $cantidad = $profesional->turnos()
                ->where('estado', '!=', EstadoTurno::Cancelado)
                ->where('inicio', '>=', now())
                ->count();

            if ($cantidad > 0) {
                return $cantidad;
            }

            $profesional->update(['activo' => false]);
            $profesional->user->tokens()->delete();

            return 0;
        });
    }

    public function reactivar(Profesional $profesional): void
    {
        $profesional->update(['activo' => true]);
    }
}
