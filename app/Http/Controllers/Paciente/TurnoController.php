<?php

namespace App\Http\Controllers\Paciente;

use App\Exceptions\HorarioNoDisponible;
use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitarTurnoRequest;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TurnoController extends Controller
{
    public function store(SolicitarTurnoRequest $request, ReservaService $reservaService): RedirectResponse
    {
        $profesional = Profesional::findOrFail($request->input('profesional'));
        $servicio = Servicio::findOrFail($request->input('servicio'));
        $paciente = Paciente::findOrFail($request->input('paciente_id'));

        $tz = config('app.timezone', 'America/Asuncion');
        $inicio = Carbon::parse($request->input('fecha').' '.$request->input('hora'), $tz);

        try {
            $turno = $reservaService->solicitar(
                $profesional,
                $servicio,
                $paciente,
                $inicio,
                $request->user(),
            );

            return redirect()->route('paciente.turnos.show', $turno);
        } catch (HorarioNoDisponible) {
            return redirect()->route('paciente.reservar', [
                'profesional' => $profesional->id,
                'servicio' => $servicio->id,
                'fecha' => $request->input('fecha'),
            ])->with('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');
        }
    }

    public function show(Turno $turno): View
    {
        $user = auth()->user();
        $turno->load(['paciente', 'profesional', 'servicio']);

        $esPropio = $turno->paciente && $turno->paciente->user_id === $user->id;
        $esDependiente = $turno->paciente && $turno->paciente->responsable_id === $user->id;

        if (! $esPropio && ! $esDependiente) {
            abort(403);
        }

        return view('paciente.turno', [
            'turno' => $turno,
        ]);
    }
}
