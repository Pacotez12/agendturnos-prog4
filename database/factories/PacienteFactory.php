<?php

namespace Database\Factories;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->paciente(),
            'responsable_id' => null,
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'tipo_documento' => 'CI',
            'documento' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'telefono' => fake()->phoneNumber(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
        ];
    }
}
