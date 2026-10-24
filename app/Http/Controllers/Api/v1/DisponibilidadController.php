<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ConsultarDisponibilidadRequest;
use App\Http\Resources\DisponibilidadResource;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DisponibilidadController extends Controller
{
    public function __construct(
        protected DisponibilidadService $disponibilidadService,
    ) {}

    /**
     * Consultar franjas horarias disponibles.
     *
     * Retorna los intervalos de inicio y fin disponibles para agendar un turno con un profesional y servicio específicos en una fecha determinada (parámetros requeridos: profesional_id, servicio_id y fecha en formato YYYY-MM-DD).
     *
     * @unauthenticated
     */
    public function index(ConsultarDisponibilidadRequest $request): AnonymousResourceCollection
    {
        $profesional = Profesional::findOrFail($request->integer('profesional_id'));
        $servicio = Servicio::findOrFail($request->integer('servicio_id'));

        $tz = config('app.timezone', 'America/Asuncion');
        $fecha = Carbon::createFromFormat('Y-m-d', (string) $request->input('fecha'), $tz)->startOfDay();

        $franjas = $this->disponibilidadService->franjasLibres($profesional, $servicio, $fecha);

        $libres = collect($franjas)
            ->where('libre', true)
            ->map(function (array $franja) use ($fecha, $servicio, $tz) {
                $inicio = Carbon::parse("{$fecha->toDateString()} {$franja['hora']}", $tz);
                $fin = $inicio->copy()->addMinutes($servicio->duracion_min);

                return [
                    'inicio' => $inicio->toIso8601String(),
                    'fin' => $fin->toIso8601String(),
                ];
            })
            ->values();

        return DisponibilidadResource::collection($libres);
    }
}
