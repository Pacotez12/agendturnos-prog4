<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\EspecialidadRequest;
use App\Models\Especialidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EspecialidadController extends Controller
{
    /**
     * Display a listing of the specialties.
     */
    public function index(): View
    {
        $especialidades = Especialidad::withCount('servicios')
            ->orderBy('nombre')
            ->get();

        return view('panel.especialidades.index', [
            'especialidades' => $especialidades,
        ]);
    }

    /**
     * Show the form for creating a new specialty.
     */
    public function create(): View
    {
        return view('panel.especialidades.create');
    }

    /**
     * Store a newly created specialty in storage.
     */
    public function store(EspecialidadRequest $request): RedirectResponse
    {
        Especialidad::create($request->validated());

        return redirect()
            ->route('panel.especialidades')
            ->with('success', 'Especialidad guardada.');
    }

    /**
     * Show the form for editing the specified specialty.
     */
    public function edit(Especialidad $especialidad): View
    {
        return view('panel.especialidades.edit', [
            'especialidad' => $especialidad,
        ]);
    }

    /**
     * Update the specified specialty in storage.
     */
    public function update(EspecialidadRequest $request, Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update($request->validated());

        return redirect()
            ->route('panel.especialidades')
            ->with('success', 'Especialidad guardada.');
    }
}
