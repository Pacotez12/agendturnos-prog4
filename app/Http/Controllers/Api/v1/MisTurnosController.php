<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Resources\TurnoResource;
use App\Services\AgendaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MisTurnosController extends Controller
{
    public function __construct(
        protected AgendaService $agendaService,
    ) {}

    /**
     * Listar turnos del paciente autenticado.
     *
     * Retorna el listado paginado de turnos pertenecientes al paciente autenticado. Por defecto devuelve turnos futuros/pendientes; si se especifica historial=1 o true, lista el historial completo (pasados, cancelados y completados).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if ($user->rol !== RolUsuario::Paciente) {
            abort(403, 'Acceso restringido únicamente a pacientes.');
        }

        $historial = $request->boolean('historial');
        $turnos = $this->agendaService->turnosDelPaciente($user, historial: $historial)
            ->paginate(20);

        return TurnoResource::collection($turnos);
    }
}
