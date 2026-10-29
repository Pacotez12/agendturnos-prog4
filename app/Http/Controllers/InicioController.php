<?php

namespace App\Http\Controllers;

use App\Models\Profesional;
use Illuminate\Contracts\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        $profesionales = Profesional::activas()->with(['servicios.especialidad'])
            ->ordenados()
            ->get();

        return view('inicio', [
            'profesionales' => $profesionales,
        ]);
    }
}
