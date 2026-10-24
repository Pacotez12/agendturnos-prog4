<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\AgendaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(Request $request, AgendaService $agenda): View
    {
        $datos = $agenda->datosInicioPanel($request->user());

        return view('panel.inicio', $datos);
    }
}
