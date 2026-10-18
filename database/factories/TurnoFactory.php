<?php

namespace Database\Factories;

use App\Enums\EstadoTurno;
use App\Models\Consultorio;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Turno>
 */
class TurnoFactory extends Factory
{
    protected $model = Turno::class;

    protected static int $offset = 0;

    public function definition(): array
    {
        self::$offset += 35;
        $inicio = Carbon::now()->addDays(7)->startOfDay()->addHours(8)->addMinutes(self::$offset);
        $fin = (clone $inicio)->addMinutes(30);

        $profesional = Profesional::factory()->create();
        $servicio = Servicio::factory()->create();
        $profesional->servicios()->attach($servicio->id);

        return [
            'paciente_id' => Paciente::factory(),
            'profesional_id' => $profesional->id,
            'servicio_id' => $servicio->id,
            'consultorio_id' => Consultorio::factory(),
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => EstadoTurno::Solicitado,
            'observaciones' => null,
            'motivo_cancelacion' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Turno $turno) {
            if ($turno->profesional_id && $turno->servicio_id) {
                DB::table('profesional_servicio')->insertOrIgnore([
                    'profesional_id' => $turno->profesional_id,
                    'servicio_id' => $turno->servicio_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
