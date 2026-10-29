<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ActualizarContactoPacienteRequest;
use App\Http\Requests\Panel\GuardarPacienteOperadorRequest;
use App\Models\Paciente;
use App\Services\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PacienteController extends Controller
{
    public function index(Request $request, PacienteService $pacienteService): View
    {
        Gate::authorize('viewAny', Paciente::class);

        $q = $request->input('q');
        $datos = $pacienteService->listarPara($request->user(), $q);

        return view('panel.pacientes.index', array_merge($datos, [
            'q' => $q,
        ]));
    }

    public function show(Request $request, Paciente $paciente, PacienteService $pacienteService): View
    {
        Gate::authorize('view', $paciente);

        $datos = $pacienteService->obtenerFicha($request->user(), $paciente);

        return view('panel.pacientes.show', $datos);
    }

    public function actualizarContacto(
        ActualizarContactoPacienteRequest $request,
        Paciente $paciente,
        PacienteService $pacienteService
    ): RedirectResponse {
        $pacienteService->actualizarContacto($paciente, $request->validated());

        return redirect()
            ->route('panel.pacientes.show', $paciente)
            ->with('success', 'Datos de contacto actualizados correctamente.');
    }

    public function store(GuardarPacienteOperadorRequest $request, PacienteService $pacienteService): RedirectResponse
    {
        $data = $request->validated();

        $responsableId = null;
        if (! empty($data['responsable_documento'])) {
            $responsable = $pacienteService->resolverResponsablePorDocumento($data['responsable_documento']);
            $responsableId = $responsable?->user_id;
        }

        $paciente = Paciente::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'tipo_documento' => $data['tipo_documento'],
            'documento' => $data['documento'],
            'telefono' => $data['telefono'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'responsable_id' => $responsableId,
            'user_id' => null,
        ]);

        return redirect()
            ->route('panel.turnos.crear', ['paciente' => $paciente->id])
            ->with('success', 'Paciente registrado correctamente.');
    }
}
