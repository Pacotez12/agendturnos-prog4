<?php

namespace Database\Factories;

use App\Models\Consultorio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultorio>
 */
class ConsultorioFactory extends Factory
{
    protected $model = Consultorio::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Consultorio '.fake()->unique()->numberBetween(1, 9999),
            'direccion' => fake()->address(),
            'telefono' => fake()->phoneNumber(),
            'activo' => true,
        ];
    }
}
