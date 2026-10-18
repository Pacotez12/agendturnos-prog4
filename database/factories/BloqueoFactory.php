<?php

namespace Database\Factories;

use App\Models\Bloqueo;
use App\Models\Profesional;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Bloqueo>
 */
class BloqueoFactory extends Factory
{
    protected $model = Bloqueo::class;

    protected static int $offset = 0;

    public function definition(): array
    {
        self::$offset += 3;
        $inicio = Carbon::now()->addDays(10)->startOfDay()->addHours(8 + self::$offset);
        $fin = (clone $inicio)->addHours(2);

        return [
            'profesional_id' => Profesional::factory(),
            'consultorio_id' => null,
            'inicio' => $inicio,
            'fin' => $fin,
            'motivo' => fake()->sentence(),
        ];
    }
}
