<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\BloqueoRequest;
use App\Http\Requests\Panel\HorarioSemanalRequest;
use App\Models\Bloqueo;
use App\Models\Consultorio;
use App\Models\Profesional;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    private function resolverProfesional(Request $request): Profesional
    {
        $user = $request->user();

        if ($user->rol === RolUsuario::Profesional) {
            $profesional = $user->profesional;
            abort_unless($profesional, 403);

            if ($request->filled('profesional_id') && (int) $request->input('profesional_id') !== $profesional->id) {
                abort(403, 'No tenés permiso para ver o editar otra profesional.');
            }

            return $profesional;
        }

        if ($user->rol === RolUsuario::Funcionario) {
            if ($request->filled('profesional_id')) {
                return Profesional::findOrFail($request->input('profesional_id'));
            }

            return Profesional::activas()->ordenados()->firstOrFail();
        }

        abort(403);
    }

    public function index(Request $request, DisponibilidadService $disponibilidadService): View
    {
        if ($request->user()->rol === RolUsuario::Funcionario
            && ! $request->filled('profesional_id') && ! Profesional::activas()->exists()) {
            return view('panel.profesionales.sin-activas');
        }

        $profesional = $this->resolverProfesional($request);

        $profesionales = ($request->user()->rol === RolUsuario::Funcionario)
            ? Profesional::activas()->ordenados()->get()
            : collect([$profesional]);

        $disponibilidades = $profesional->disponibilidades()
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        $franjasPorDia = [];
        for ($d = 1; $d <= 7; $d++) {
            $franjasPorDia[$d] = $disponibilidades->where('dia_semana', $d)->map(fn ($disp) => [
                'dia_semana' => $disp->dia_semana,
                'hora_inicio' => substr($disp->hora_inicio, 0, 5),
                'hora_fin' => substr($disp->hora_fin, 0, 5),
            ])->values()->all();
        }

        $bloqueos = $profesional->bloqueos()
            ->orderBy('inicio', 'desc')
            ->get();

        $servicios = $profesional->servicios()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $servicioId = $request->filled('servicio_id')
            ? (int) $request->input('servicio_id')
            : $servicios->first()?->id;

        $servicioSeleccionado = $servicios->firstWhere('id', $servicioId);
        $fechaInput = $request->filled('fecha')
            ? Carbon::parse($request->input('fecha'))
            : Carbon::tomorrow();

        $horariosPreview = [];
        if ($servicioSeleccionado) {
            $horariosPreview = $disponibilidadService->franjasLibres($profesional, $servicioSeleccionado, $fechaInput);
        }

        return view('panel.disponibilidad', [
            'profesional' => $profesional,
            'profesionales' => $profesionales,
            'franjasPorDia' => $franjasPorDia,
            'bloqueos' => $bloqueos,
            'servicios' => $servicios,
            'servicioSeleccionado' => $servicioSeleccionado,
            'fechaInput' => $fechaInput,
            'horariosPreview' => $horariosPreview,
        ]);
    }

    public function guardarHorario(HorarioSemanalRequest $request): RedirectResponse
    {
        $profesional = $this->resolverProfesional($request);
        $consultorioId = Consultorio::where('activo', true)->value('id') ?? Consultorio::value('id');

        DB::transaction(function () use ($profesional, $request, $consultorioId) {
            Profesional::whereKey($profesional->id)->lockForUpdate()->first();

            $profesional->disponibilidades()->delete();

            $franjas = $request->validated('franjas', []);
            foreach ($franjas as $franja) {
                $profesional->disponibilidades()->create([
                    'consultorio_id' => $consultorioId,
                    'dia_semana' => $franja['dia_semana'],
                    'hora_inicio' => $franja['hora_inicio'],
                    'hora_fin' => $franja['hora_fin'],
                ]);
            }
        });

        $params = $request->user()->rol === RolUsuario::Funcionario ? ['profesional_id' => $profesional->id] : [];

        return redirect()
            ->route('panel.disponibilidad', $params)
            ->with('success', 'Horario semanal guardado.');
    }

    public function guardarBloqueo(BloqueoRequest $request): RedirectResponse
    {
        $profesional = $this->resolverProfesional($request);
        $desde = Carbon::parse($request->validated('desde'));
        $hasta = Carbon::parse($request->validated('hasta'));

        $turnos = $profesional->turnos()
            ->where('estado', '!=', EstadoTurno::Cancelado)
            ->where('inicio', '<', $hasta)
            ->where('fin', '>', $desde)
            ->get();

        if ($turnos->isNotEmpty() && ! $request->boolean('confirmar')) {
            $count = $turnos->count();
            $confirmados = $turnos->where('estado', EstadoTurno::Confirmado)->count();
            $solicitados = $turnos->where('estado', EstadoTurno::Solicitado)->count();
            $detalles = [];
            if ($confirmados > 0) {
                $detalles[] = "{$confirmados} ".($confirmados === 1 ? 'confirmado' : 'confirmados');
            }
            if ($solicitados > 0) {
                $detalles[] = "{$solicitados} ".($solicitados === 1 ? 'solicitado' : 'solicitados');
            }
            $detalleTexto = ! empty($detalles) ? ' ('.implode(', ', $detalles).')' : '';

            return back()
                ->withInput()
                ->withErrors([
                    'confirmar' => "En ese rango hay {$count} ".($count === 1 ? 'turno' : 'turnos')."{$detalleTexto}. No se cancelan solos: avisá a los pacientes y reprogramalos desde la agenda.",
                ]);
        }

        $profesional->bloqueos()->create([
            'inicio' => $desde,
            'fin' => $hasta,
            'motivo' => $request->validated('motivo'),
        ]);

        $params = $request->user()->rol === RolUsuario::Funcionario ? ['profesional_id' => $profesional->id] : [];

        return redirect()
            ->route('panel.disponibilidad', $params)
            ->with('success', 'Bloqueo registrado correctamente.');
    }

    public function eliminarBloqueo(Request $request, Bloqueo $bloqueo): RedirectResponse
    {
        if ($request->user()->rol === RolUsuario::Profesional) {
            abort_if($bloqueo->profesional_id !== $request->user()->profesional?->id, 403);
        }

        $profesionalId = $bloqueo->profesional_id;
        $bloqueo->delete();

        $params = $request->user()->rol === RolUsuario::Funcionario ? ['profesional_id' => $profesionalId] : [];

        return redirect()
            ->route('panel.disponibilidad', $params)
            ->with('success', 'Bloqueo eliminado correctamente.');
    }
}
