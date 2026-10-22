<?php

namespace App\Jobs;

use App\Enums\EstadoNotificacion;
use App\Models\Notificacion;
use App\Notifications\AvisoTurno;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EnviarNotificacion implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __construct(
        public Notificacion $notificacion,
        public array $datos = []
    ) {}

    public function handle(): void
    {
        $this->notificacion->refresh();

        if ($this->notificacion->estado === EstadoNotificacion::Enviada) {
            return;
        }

        $destinatario = $this->notificacion->destinatario;
        $destinatario->notify(new AvisoTurno($this->notificacion, $this->datos));

        $this->notificacion->update([
            'estado' => EstadoNotificacion::Enviada,
            'enviado_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->notificacion->update([
            'estado' => EstadoNotificacion::Fallida,
        ]);
    }
}
