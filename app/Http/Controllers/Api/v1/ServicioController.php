<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServicioResource;
use App\Models\Servicio;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServicioController extends Controller
{
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
