<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ServicioRequest;
use App\Models\Especialidad;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Servicio::with('especialidad');

        if ($request->filled('especialidad')) {
            $query->where('especialidad_id', $request->integer('especialidad'));
        }

        if ($request->filled('estado')) {
            if ($request->input('estado') === 'activo') {
                $query->where('activo', true);
            } elseif ($request->input('estado') === 'inactivo') {
                $query->where('activo', false);
            }
        }

        $servicios = $query->orderBy('especialidad_id')->orderBy('nombre')->get();
        $especialidades = Especialidad::where('activa', true)->orderBy('nombre')->get();

        return view('panel.servicios.index', [
            'servicios' => $servicios,
            'especialidades' => $especialidades,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $especialidades = Especialidad::where('activa', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->toArray();

        return view('panel.servicios.create', [
            'especialidades' => $especialidades,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServicioRequest $request): RedirectResponse
    {
        Servicio::create($request->validated());

        return redirect()
            ->route('panel.servicios')
            ->with('success', 'Servicio guardado.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Servicio $servicio): View
    {
        $especialidades = Especialidad::where('activa', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->toArray();

        return view('panel.servicios.edit', [
            'servicio' => $servicio,
            'especialidades' => $especialidades,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServicioRequest $request, Servicio $servicio): RedirectResponse
    {
        $servicio->update($request->validated());

        return redirect()
            ->route('panel.servicios')
            ->with('success', 'Servicio guardado.');
    }
}
