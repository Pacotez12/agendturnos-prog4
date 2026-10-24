<?php

namespace App\Http\Resources;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Servicio
 */
class ServicioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'duracion_min' => (int) $this->duracion_min,
            'precio' => (int) $this->precio,
            'especialidad' => [
                'id' => $this->especialidad?->id,
                'nombre' => $this->especialidad?->nombre,
            ],
            'profesionales_ids' => $this->profesionales->pluck('id')->values()->all(),
        ];
    }
}
