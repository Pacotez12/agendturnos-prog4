<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use App\Models\Profesional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfesionalController extends Controller
{
    /**
     * Display a listing of professionals.
     */
    public function index(): View
    {
        $profesionales = Profesional::withCount('servicios')
            ->orderBy('nombre')
            ->get();

        return view('panel.profesionales.index', [
            'profesionales' => $profesionales,
        ]);
    }

    /**
     * Show the form for managing services offered by the professional.
     */
    public function servicios(Profesional $profesional): View
    {
        $especialidades = Especialidad::where('activa', true)
            ->whereHas('servicios', fn ($q) => $q->where('activo', true))
            ->with(['servicios' => fn ($q) => $q->where('activo', true)->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();

        $serviciosOfrecidosIds = $profesional->servicios()->pluck('servicios.id')->all();
        $serviciosConTurnosIds = $profesional->turnos()->pluck('servicio_id')->unique()->all();

        return view('panel.profesionales.servicios', [
            'profesional' => $profesional,
            'especialidades' => $especialidades,
            'serviciosOfrecidosIds' => $serviciosOfrecidosIds,
            'serviciosConTurnosIds' => $serviciosConTurnosIds,
        ]);
    }

    /**
     * Update the services offered by the professional.
     */
    public function updateServicios(Request $request, Profesional $profesional): RedirectResponse
    {
        $request->validate([
            'servicios' => ['sometimes', 'array'],
            'servicios.*' => ['integer', 'exists:servicios,id'],
        ]);

        $serviciosIds = array_map('intval', (array) $request->input('servicios', []));
        $serviciosConTurnosIds = $profesional->turnos()
            ->pluck('servicio_id')
            ->unique()
            ->map('intval')
            ->all();

        // Los servicios inactivos (o de especialidades inactivas) no se muestran en la pantalla:
        // se conservan tal cual para no perder la asignación si se reactivan.
        $serviciosOcultosIds = $profesional->servicios()
            ->where(fn ($q) => $q->where('servicios.activo', false)
                ->orWhereHas('especialidad', fn ($e) => $e->where('activa', false)))
            ->pluck('servicios.id')
            ->map('intval')
            ->all();

        $finalServiciosIds = array_values(array_unique(array_merge($serviciosIds, $serviciosConTurnosIds, $serviciosOcultosIds)));

        $profesional->servicios()->sync($finalServiciosIds);

        return redirect()
            ->route('panel.profesionales.servicios', $profesional)
            ->with('success', 'Servicios actualizados.');
    }
}
