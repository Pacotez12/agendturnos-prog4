<?php

namespace App\Services;

use App\Models\Profesional;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReservaService
{
    public function __construct(private DisponibilidadService $disponibilidad) {}

    /**
     * Arma los datos del formulario de reserva según lo que se eligió hasta ahora.
     *
     * @return array{
     *     profesionales: Collection<int, Profesional>,
     *     profesional: ?Profesional,
     *     servicios: Collection<int, Servicio>,
     *     servicio: ?Servicio,
     *     fecha: ?Carbon,
     *     horarios: list<array{hora: string, libre: bool}>,
     * }
     */
    public function opciones(?int $profesionalId, ?int $servicioId, ?string $fecha): array
    {
        $profesionales = Profesional::orderBy('apellido')->orderBy('nombre')->get();
        $profesional = $profesionalId ? $profesionales->firstWhere('id', $profesionalId) : null;

        $servicios = $profesional
            ? $profesional->servicios()->where('activo', true)->orderBy('nombre')->get()
            : new Collection;
        $servicio = $servicioId ? $servicios->firstWhere('id', $servicioId) : null;

        $dia = ($servicio && $fecha) ? Carbon::parse($fecha, config('app.timezone')) : null;

        return [
            'profesionales' => $profesionales,
            'profesional' => $profesional,
            'servicios' => $servicios,
            'servicio' => $servicio,
            'fecha' => $dia,
            'horarios' => $dia ? $this->disponibilidad->franjasLibres($profesional, $servicio, $dia) : [],
        ];
    }
}
