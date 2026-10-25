<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsultaHorariosRequest;
use App\Services\ReservaService;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function create(ConsultaHorariosRequest $request, ReservaService $reservaService): View
    {
        $user = $request->user();

        $datos = $reservaService->opciones(
            $request->integer('profesional') ?: null,
            $request->integer('servicio') ?: null,
            $request->input('fecha'),
        );

        $pacienteActual = $user?->paciente;
        $familiares = $user ? $user->dependientes()->orderBy('nombre')->get() : collect();

        // "Pedir turno" desde Mi familia llega con ?para=<id>; solo vale si es un familiar propio.
        $para = $familiares->firstWhere('id', $request->integer('para'));

        return view('paciente.reservar', array_merge($datos, [
            'pacienteActual' => $pacienteActual,
            'familiares' => $familiares,
            'para' => $para,
            'preseleccionado' => $para?->id ?? $pacienteActual?->id ?? $familiares->first()?->id,
        ]));
    }
}
