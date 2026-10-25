<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarFamiliarRequest;
use App\Models\Paciente;
use App\Services\FamiliaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamiliaController extends Controller
{
    public function index(Request $request, FamiliaService $familia): View
    {
        return view('paciente.familia', [
            'familiares' => $familia->listar($request->user()),
        ]);
    }

    public function store(GuardarFamiliarRequest $request, FamiliaService $familia): RedirectResponse
    {
        $familiar = $familia->agregar($request->user(), $request->validated());

        return redirect()->route('paciente.familia')
            ->with('success', "Agregaste a {$familiar->nombreCompleto()} a tu familia.");
    }

    public function update(GuardarFamiliarRequest $request, Paciente $paciente, FamiliaService $familia): RedirectResponse
    {
        $familia->actualizar($paciente, $request->validated());

        return redirect()->route('paciente.familia')
            ->with('success', "Guardamos los datos de {$paciente->nombreCompleto()}.");
    }
}
