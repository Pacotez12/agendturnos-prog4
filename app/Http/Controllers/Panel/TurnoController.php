<?php

namespace App\Http\Controllers\Panel;

use App\Enums\RolUsuario;
use App\Exceptions\HorarioNoDisponible;
use App\Exceptions\TransicionInvalida;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\AgendarTurnoOperadorRequest;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Services\AgendaService;
use App\Services\DisponibilidadService;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TurnoController extends Controller
{
    public function solicitudes(Request $request, AgendaService $agenda): View
    {
        Gate::authorize('viewAny', Turno::class);

        $user = $request->user();
        $solicitudes = $agenda->solicitudesPendientes($user)
            ->with(['paciente', 'servicio', 'profesional'])
            ->orderBy('inicio', 'asc')
            ->get();

        return view('panel.solicitudes', [
            'solicitudes' => $solicitudes,
            'esFuncionario' => $user->rol === RolUsuario::Funcionario,
        ]);
    }

    public function crear(Request $request, ReservaService $reserva): View
    {
        $user = $request->user();
        $q = trim((string) $request->input('q'));
        $pacientes = collect();
        if (mb_strlen($q) >= 3) {
            $pacientes = Paciente::buscar($q)->with('responsable.paciente')->get();
        }

        $pacienteSeleccionado = null;
        if ($request->filled('paciente')) {
            $pacienteSeleccionado = Paciente::with(['responsable.paciente'])->find($request->input('paciente'));
        }

        $profesionalId = $user->rol === RolUsuario::Profesional
            ? $user->profesional?->id
            : ($request->filled('profesional') ? (int) $request->input('profesional') : null);

        $servicioId = $request->filled('servicio') ? (int) $request->input('servicio') : null;
        $fecha = $request->input('fecha');

        $opciones = $reserva->opciones($profesionalId, $servicioId, $fecha);

        return view('panel.turnos.crear', array_merge($opciones, [
            'q' => $q,
            'pacientes' => $pacientes,
            'pacienteSeleccionado' => $pacienteSeleccionado,
            'esFuncionario' => $user->rol === RolUsuario::Funcionario,
            'nuevoPaciente' => $request->boolean('nuevo_paciente') || $request->session()->has('errors'),
        ]));
    }

    public function store(AgendarTurnoOperadorRequest $request, ReservaService $reserva): RedirectResponse
    {
        $data = $request->validated();
        $paciente = Paciente::findOrFail($data['paciente_id']);
        $profesional = Profesional::findOrFail($data['profesional']);
        $servicio = Servicio::findOrFail($data['servicio']);

        $tz = config('app.timezone', 'America/Asuncion');
        $inicio = Carbon::parse($data['fecha'].' '.$data['hora'], $tz);

        try {
            $turno = $reserva->agendar($profesional, $servicio, $paciente, $inicio, $request->user());

            return redirect()
                ->route('panel.turnos.show', $turno)
                ->with('success', 'Turno agendado y confirmado.');
        } catch (HorarioNoDisponible) {
            return redirect()
                ->route('panel.turnos.crear', [
                    'paciente' => $paciente->id,
                    'profesional' => $profesional->id,
                    'servicio' => $servicio->id,
                    'fecha' => $data['fecha'],
                ])
                ->with('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');
        }
    }

    public function show(Turno $turno): View
    {
        Gate::authorize('view', $turno);

        $turno->load([
            'paciente.user',
            'paciente.responsable.paciente',
            'paciente.responsable.profesional',
            'profesional',
            'servicio',
            'consultorio',
            'historial' => fn ($q) => $q->with('usuario.paciente', 'usuario.profesional')->orderBy('created_at', 'asc'),
        ]);

        return view('panel.turnos.show', [
            'turno' => $turno,
        ]);
    }

    public function confirmar(Turno $turno, AgendaService $agenda): RedirectResponse
    {
        Gate::authorize('operate', $turno);

        try {
            $agenda->confirmar($turno, auth()->user());

            return back()->with('success', 'Turno confirmado.');
        } catch (TransicionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelar(Request $request, Turno $turno, AgendaService $agenda): RedirectResponse
    {
        Gate::authorize('operate', $turno);

        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $agenda->cancelar($turno, $validated['motivo'] ?? null, auth()->user());

            return back()->with('success', 'Turno cancelado.');
        } catch (TransicionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function atendido(Turno $turno, AgendaService $agenda): RedirectResponse
    {
        Gate::authorize('operate', $turno);

        try {
            $agenda->marcarAtendido($turno, auth()->user());

            return back()->with('success', 'Turno marcado como atendido.');
        } catch (TransicionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ausente(Turno $turno, AgendaService $agenda): RedirectResponse
    {
        Gate::authorize('operate', $turno);

        try {
            $agenda->marcarAusente($turno, auth()->user());

            return back()->with('success', 'Turno marcado como ausente.');
        } catch (TransicionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reprogramar(Request $request, Turno $turno, DisponibilidadService $disponibilidad): View
    {
        Gate::authorize('operate', $turno);

        $fecha = null;
        $horarios = [];

        if ($request->filled('fecha')) {
            $request->validate([
                'fecha' => ['date_format:Y-m-d', 'after_or_equal:today'],
            ]);

            $fecha = Carbon::parse($request->input('fecha'), config('app.timezone'));
            $horarios = $disponibilidad->franjasLibres($turno->profesional, $turno->servicio, $fecha, $turno);
        }

        return view('panel.turnos.reprogramar', [
            'turno' => $turno,
            'fecha' => $fecha,
            'horarios' => $horarios,
        ]);
    }

    public function updateReprogramar(Request $request, Turno $turno, AgendaService $agenda): RedirectResponse
    {
        Gate::authorize('operate', $turno);

        $validated = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
        ]);

        $tz = config('app.timezone', 'America/Asuncion');
        $nuevoInicio = Carbon::parse($validated['fecha'].' '.$validated['hora'], $tz);

        try {
            $agenda->reprogramar($turno, $nuevoInicio, auth()->user());

            return redirect()->route('panel.turnos.show', $turno)->with('success', 'Turno reprogramado.');
        } catch (HorarioNoDisponible) {
            return redirect()->route('panel.turnos.reprogramar', [
                $turno,
                'fecha' => $validated['fecha'],
            ])->with('error', 'Ese horario acaba de ser tomado. Elegí otro, ya actualizamos la lista.');
        } catch (TransicionInvalida $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
