<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\GuardarPacienteOperadorRequest;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;

class PacienteController extends Controller
{
    public function store(GuardarPacienteOperadorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $responsableId = null;
        if (! empty($data['responsable_documento'])) {
            $responsable = Paciente::where('documento', $data['responsable_documento'])->first();
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
