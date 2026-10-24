<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;
use App\Support\Telefono;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PacienteService
{
    /**
     * @return array{pacientes: LengthAwarePaginator, total: int}
     */
    public function listarPara(User $user, ?string $busqueda = null, int $porPagina = 20): array
    {
        $query = Paciente::query();

        if ($user->rol === RolUsuario::Profesional && $user->profesional) {
            $profesionalId = $user->profesional->id;
            $query->whereHas('turnos', fn ($q) => $q->where('profesional_id', $profesionalId));
        }

        $total = (clone $query)->count();

        if ($busqueda && mb_strlen(trim($busqueda)) >= 3) {
            $query->buscar(trim($busqueda));
        } else {
            $query->orderBy('apellido')->orderBy('nombre');
        }

        $pacientes = $query
            ->with([
                'user.dependientes',
                'responsable.paciente',
                'turnos' => function ($q) use ($user) {
                    $q->where('inicio', '>=', now())
                        ->where('estado', '!=', EstadoTurno::Cancelado)
                        ->when($user->rol === RolUsuario::Profesional && $user->profesional, fn ($tq) => $tq->where('profesional_id', $user->profesional->id))
                        ->orderBy('inicio', 'asc');
                },
            ])
            ->paginate($porPagina)
            ->withQueryString();

        return [
            'pacientes' => $pacientes,
            'total' => $total,
        ];
    }

    /**
     * @return array{
     *     paciente: Paciente,
     *     familiares: Collection<int, Paciente>,
     *     responsablePaciente: ?Paciente,
     *     resumenTurnos: array{atendidos: int, ausentes: int, cancelados: int},
     *     turnos: LengthAwarePaginator
     * }
     */
    public function obtenerFicha(User $user, Paciente $paciente, int $porPaginaTurnos = 20): array
    {
        $paciente->load(['user', 'responsable.paciente']);

        $familiares = collect();
        if ($paciente->user_id) {
            $familiares = Paciente::where('responsable_id', $paciente->user_id)
                ->orderBy('nombre')
                ->get();
        }

        $responsablePaciente = $paciente->responsable?->paciente;

        $turnosQuery = $paciente->turnos()
            ->when($user->rol === RolUsuario::Profesional && $user->profesional, fn ($q) => $q->where('profesional_id', $user->profesional->id));

        $resumenTurnos = [
            'atendidos' => (clone $turnosQuery)->where('estado', EstadoTurno::Atendido)->count(),
            'ausentes' => (clone $turnosQuery)->where('estado', EstadoTurno::Ausente)->count(),
            'cancelados' => (clone $turnosQuery)->where('estado', EstadoTurno::Cancelado)->count(),
        ];

        $turnos = (clone $turnosQuery)
            ->with(['servicio', 'profesional'])
            ->orderBy('inicio', 'desc')
            ->paginate($porPaginaTurnos, ['*'], 'turnos_page')
            ->withQueryString();

        return [
            'paciente' => $paciente,
            'familiares' => $familiares,
            'responsablePaciente' => $responsablePaciente,
            'resumenTurnos' => $resumenTurnos,
            'turnos' => $turnos,
        ];
    }

    /**
     * @param  array{telefono?: ?string}  $datos
     */
    public function actualizarContacto(Paciente $paciente, array $datos): void
    {
        $telefono = isset($datos['telefono']) && trim((string) $datos['telefono']) !== ''
            ? (Telefono::normalizar($datos['telefono']) ?? $datos['telefono'])
            : null;

        $updates = [
            'telefono' => $telefono,
        ];

        $paciente->update($updates);
    }
}
