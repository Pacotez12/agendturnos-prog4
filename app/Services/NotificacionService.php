<?php

namespace App\Services;

use App\Enums\EstadoNotificacion;
use App\Jobs\EnviarNotificacion;
use App\Models\Notificacion;
use App\Models\Turno;
use App\Models\User;

class NotificacionService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function programar(Turno $turno, string $tipo, array $datos = []): ?Notificacion
    {
        $destinatario = $turno->paciente->user ?? $turno->paciente->responsable;

        if (! $destinatario) {
            return null;
        }

        $notificacion = Notificacion::updateOrCreate(
            [
                'turno_id' => $turno->id,
                'tipo' => $tipo,
                'destinatario_id' => $destinatario->id,
            ],
            [
                'canal' => 'correo',
                'estado' => EstadoNotificacion::Pendiente,
                'enviado_at' => null,
            ]
        );

        EnviarNotificacion::dispatch($notificacion, $datos)->afterCommit();

        return $notificacion;
    }

    public function reenviarRecordatorio(Turno $turno, User $usuario): ?Notificacion
    {
        $notificacion = $this->programar($turno, 'recordatorio');

        $turno->historial()->create([
            'estado_anterior' => $turno->estado,
            'estado_nuevo' => $turno->estado,
            'usuario_id' => $usuario->id,
            'detalle' => 'Recordatorio reenviado',
        ]);

        return $notificacion;
    }
}
