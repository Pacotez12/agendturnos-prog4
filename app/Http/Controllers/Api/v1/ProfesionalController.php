<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfesionalResource;
use App\Models\Profesional;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfesionalController extends Controller
{
    /**
     * Listar profesionales activos.
     *
     * Retorna la lista de profesionales activos de la clínica ordenados alfabéticamente, incluyendo los servicios que realiza cada uno y su especialidad.
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        $profesionales = Profesional::activas()->with(['servicios.especialidad'])
            ->ordenados()
            ->get();

        return ProfesionalResource::collection($profesionales);
    }
}
