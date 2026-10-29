<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    /**
     * Display a listing of active services grouped by specialty.
     */
    public function index(Request $request): View
    {
        $especialidadSeleccionada = $request->filled('especialidad')
            ? $request->integer('especialidad')
            : null;

        $especialidades = Especialidad::where('activa', true)
            ->whereHas('servicios', fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->get();

        $query = Servicio::with(['especialidad', 'profesionales' => fn ($q) => $q->activas()])
            ->where('activo', true)
            ->whereHas('especialidad', fn ($q) => $q->where('activa', true));

        if ($especialidadSeleccionada) {
            $query->where('especialidad_id', $especialidadSeleccionada);
        }

        $servicios = $query->orderBy('nombre')->get();

        $serviciosPorEspecialidad = $servicios->groupBy(fn (Servicio $s) => $s->especialidad->nombre);

        return view('servicios.catalogo', [
            'especialidades' => $especialidades,
            'serviciosPorEspecialidad' => $serviciosPorEspecialidad,
            'especialidadSeleccionada' => $especialidadSeleccionada,
            'totalServicios' => $servicios->count(),
        ]);
    }
}
