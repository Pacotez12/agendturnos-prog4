<?php

namespace App\Http\Controllers\Paciente;

use App\Exceptions\HorarioNoDisponible;
use App\Exceptions\PacienteConTurnoEnHorario;
use App\Exceptions\TransicionInvalida;
use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitarTurnoRequest;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Services\AgendaService;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TurnoController extends Controller
{
    public function index(Request $request, AgendaService $agenda): View
    {
        $user = $request->user();
        $ver = $request->input('ver') === 'historial' ? 'historial' : 'proximos';
        $esHistorial = $ver === 'historial';

        $turnos = $esHistorial
            ? $agenda->turnosDelPaciente($user, historial: true)->paginate(10)->withQueryString()
            : $agenda->turnosDelPaciente($user, historial: false)->get();

        return view('paciente.turnos', [
            'turnos' => $turnos,
            'ver' => $ver,
            'proximosCount' => $agenda->conteoProximosDelPaciente($user),
            'agenda' => $agenda,
        ]);
    }

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
        } catch (HorarioNoDisponible|PacienteConTurnoEnHorario $e) {
            return redirect()->route('paciente.reservar', [
                'profesional' => $profesional->id,
                'servicio' => $servicio->id,
                'fecha' => $request->input('fecha'),
                'para' => $paciente->id,
            ])->with('error', $e instanceof PacienteConTurnoEnHorario
                ? $e->getMessage()
                : 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');
        }
    }

    public function show(Turno $turno): View
    {
        $user = auth()->user();

        if (! in_array($turno->paciente_id, $user->pacientesIds(), true)) {
            abort(403);
        }

        $turno->load(['paciente', 'profesional', 'servicio']);

        return view('paciente.turno', [
            'turno' => $turno,
        ]);
    }

    public function cancelar(Turno $turno, AgendaService $agenda): RedirectResponse
    {
        $user = auth()->user();

        if (! in_array($turno->paciente_id, $user->pacientesIds(), true)) {
            abort(403);
        }

        try {
            $agenda->cancelar(
                turno: $turno,
                motivo: null,
                usuario: $user,
                notificar: false,
                detalle: 'Cancelado por el paciente'
            );

            return redirect()->route('paciente.turnos')->with('success', 'El turno fue cancelado.');
        } catch (TransicionInvalida $e) {
            return redirect()->route('paciente.turnos')->with('error', $e->getMessage());
        }
    }
}
