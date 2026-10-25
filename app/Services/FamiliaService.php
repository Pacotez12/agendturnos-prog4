<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Collection;

class FamiliaService
{
    /**
     * Familiares a cargo del usuario, cada uno con su próximo turno activo
     * (solicitado o confirmado) en la relación `turnos`.
     *
     * @return Collection<int, Paciente>
     */
    public function listar(User $user): Collection
    {
        return $user->dependientes()
            ->with(['turnos' => function ($q) {
                $q->with('servicio')
                    ->where('inicio', '>=', now())
                    ->whereIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado])
                    ->orderBy('inicio')
                    ->limit(1);
            }])
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get();
    }

    /**
     * Registra un paciente sin cuenta propia a cargo del usuario.
     *
     * @param  array{nombre: string, apellido: string, tipo_documento: string, documento: string, fecha_nacimiento: string}  $datos
     */
    public function agregar(User $user, array $datos): Paciente
    {
        return Paciente::create([
            ...$datos,
            'user_id' => null,
            'responsable_id' => $user->id,
        ]);
    }

    /**
     * @param  array{nombre: string, apellido: string, tipo_documento: string, documento: string, fecha_nacimiento: string}  $datos
     */
    public function actualizar(Paciente $familiar, array $datos): Paciente
    {
        $familiar->update($datos);

        return $familiar;
    }
}
