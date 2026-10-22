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
        $datos = $reservaService->opciones(
            $request->integer('profesional') ?: null,
            $request->integer('servicio') ?: null,
            $request->input('fecha'),
        );

        return view('paciente.reservar', $datos);
    }
}
