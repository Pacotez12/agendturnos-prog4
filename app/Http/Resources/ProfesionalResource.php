<?php

namespace App\Http\Resources;

use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Profesional
 */
class ProfesionalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $especialidades = $this->servicios
            ->pluck('especialidad')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($esp) => [
                'id' => $esp->id,
                'nombre' => $esp->nombre,
            ])
            ->all();

        $servicios = $this->servicios
            ->where('activo', true)
            ->values()
            ->map(fn ($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
            ])
            ->all();

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->nombreCompleto(),
            'especialidades' => $especialidades,
            'servicios' => $servicios,
        ];
    }
}
