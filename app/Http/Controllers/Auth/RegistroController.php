<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Services\RegistroPacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistroController extends Controller
{
    protected $registroService;

    public function __construct(RegistroPacienteService $registroService)
    {
        $this->registroService = $registroService;
    }

    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(RegistroRequest $request): RedirectResponse
    {
        $user = $this->registroService->registrar($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('paciente.inicio') // Ajustar la ruta de redirección según el home del paciente
            ->with('success', '¡Bienvenido a Glow Dental Protect! Tu registro se ha completado con éxito.');
    }
}
