<?php

namespace Database\Factories;

use App\Models\Especialidad;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servicio>
 */
class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    public function definition(): array
    {
        return [
            'especialidad_id' => Especialidad::factory(),
            'nombre' => fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'duracion_min' => 30,
            'precio' => fake()->numberBetween(50000, 300000),
            'activo' => true,
        ];
    }
}
