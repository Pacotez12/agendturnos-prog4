<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Services\AgendaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(Request $request, AgendaService $agenda): View
    {
        $datos = $agenda->datosInicioPaciente($request->user());

        return view('paciente.inicio', $datos);
    }
}
