<?php

namespace Database\Factories;

use App\Enums\EstadoNotificacion;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notificacion>
 */
class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    public function definition(): array
    {
        return [
            'destinatario_id' => User::factory()->paciente(),
            'turno_id' => null,
            'tipo' => 'confirmacion',
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
            'enviado_at' => null,
        ];
    }
}
