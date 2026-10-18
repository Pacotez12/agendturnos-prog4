<?php

namespace Database\Factories;

use App\Enums\EstadoTurno;
use App\Models\HistorialTurno;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistorialTurno>
 */
class HistorialTurnoFactory extends Factory
{
    protected $model = HistorialTurno::class;

    public function definition(): array
    {
        return [
            'turno_id' => Turno::factory(),
            'estado_anterior' => null,
            'estado_nuevo' => EstadoTurno::Solicitado,
            'usuario_id' => null,
        ];
    }
}
