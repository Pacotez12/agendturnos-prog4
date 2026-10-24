<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServicioResource;
use App\Models\Servicio;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServicioController extends Controller
{
    /**
     * Listar servicios activos.
     *
     * Retorna el catálogo de servicios odontológicos activos pertenecientes a especialidades activas, con su precio en guaraníes, duración estimada e identificadores de profesionales que los prestan.
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        $servicios = Servicio::with(['especialidad', 'profesionales'])
            ->where('activo', true)
            ->whereHas('especialidad', fn ($q) => $q->where('activa', true))
            ->orderBy('nombre')
            ->get();

        return ServicioResource::collection($servicios);
    }
}
