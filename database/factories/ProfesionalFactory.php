<?php

namespace Database\Factories;

use App\Models\Profesional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesional>
 */
class ProfesionalFactory extends Factory
{
    protected $model = Profesional::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->profesional(),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'matricula' => (string) fake()->unique()->numerify('MAT-#####'),
            'orden' => 100,
        ];
    }
}
