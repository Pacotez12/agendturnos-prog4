<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfesionalResource;
use App\Models\Profesional;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfesionalController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $profesionales = Profesional::with(['servicios.especialidad'])
            ->ordenados()
            ->get();

        return ProfesionalResource::collection($profesionales);
    }
}
