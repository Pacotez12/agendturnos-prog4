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
