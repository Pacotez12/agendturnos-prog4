<?php

namespace Database\Factories;

use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Profesional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disponibilidad>
 */
class DisponibilidadFactory extends Factory
{
    protected $model = Disponibilidad::class;

    public function definition(): array
    {
        return [
            'profesional_id' => Profesional::factory(),
            'consultorio_id' => Consultorio::factory(),
            'dia_semana' => fake()->numberBetween(1, 5),
            'hora_inicio' => '08:00:00',
            'hora_fin' => '12:00:00',
        ];
    }
}
