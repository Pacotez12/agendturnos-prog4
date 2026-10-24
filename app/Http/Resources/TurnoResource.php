<?php

namespace App\Http\Resources;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Turno
 */
class TurnoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $inicio = $this->inicio ? $this->inicio->copy()->setTimezone($tz) : null;
        $fin = $this->fin ? $this->fin->copy()->setTimezone($tz) : null;

        return [
            'id' => $this->id,
            'inicio' => $inicio?->toIso8601String(),
            'fin' => $fin?->toIso8601String(),
            'estado' => [
                'valor' => $this->estado->value,
                'etiqueta' => $this->estado->etiqueta(),
            ],
            'servicio' => [
                'id' => $this->servicio?->id,
                'nombre' => $this->servicio?->nombre,
            ],
            'profesional' => [
                'id' => $this->profesional?->id,
                'nombre_completo' => $this->profesional?->nombreCompleto(),
            ],
            'paciente' => [
                'id' => $this->paciente?->id,
                'nombre' => $this->paciente?->nombreCompleto(),
            ],
        ];
    }
}
