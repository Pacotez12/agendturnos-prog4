<?php

namespace App\Services;

use App\Enums\EstadoNotificacion;
use App\Jobs\EnviarNotificacion;
use App\Models\Notificacion;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class NotificacionService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function programar(Turno $turno, string $tipo, array $datos = [], bool $inmediato = false): ?Notificacion
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

        $job = EnviarNotificacion::dispatch($notificacion, $datos);

        $momento = ! $inmediato ? $this->momentoDeEnvio(Carbon::now(config('app.timezone'))) : null;

        if ($momento) {
            $job->delay($momento);
        }

        $job->afterCommit();

        return $notificacion;
    }

    public function momentoDeEnvio(CarbonInterface $ahora): ?CarbonInterface
    {
        $timezone = config('app.timezone');
        $ahoraEnZona = $ahora->copy()->setTimezone($timezone);

        $desdeStr = config('agenda.silencio_desde', '21:00');
        $hastaStr = config('agenda.silencio_hasta', '08:00');

        $desde = $ahoraEnZona->copy()->setTimeFromTimeString($desdeStr);
        $hasta = $ahoraEnZona->copy()->setTimeFromTimeString($hastaStr);

        if ($desde > $hasta) {
            if ($ahoraEnZona >= $desde) {
                return $ahoraEnZona->copy()->addDay()->setTimeFromTimeString($hastaStr);
            }

            if ($ahoraEnZona < $hasta) {
                return $ahoraEnZona->copy()->setTimeFromTimeString($hastaStr);
            }

            return null;
        }

        if ($ahoraEnZona >= $desde && $ahoraEnZona < $hasta) {
            return $hasta;
        }

        return null;
    }

    public function reenviarRecordatorio(Turno $turno, User $usuario): ?Notificacion
    {
        $notificacion = $this->programar($turno, 'recordatorio', [], true);

        $turno->historial()->create([
            'estado_anterior' => $turno->estado,
            'estado_nuevo' => $turno->estado,
            'usuario_id' => $usuario->id,
            'detalle' => 'Recordatorio reenviado',
        ]);

        return $notificacion;
    }
}
