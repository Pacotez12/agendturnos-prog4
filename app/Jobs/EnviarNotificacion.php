<?php

namespace App\Jobs;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use App\Models\Notificacion;
use App\Notifications\AvisoTurno;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnviarNotificacion implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __construct(
        public Notificacion $notificacion,
        public array $datos = []
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $this->notificacion->refresh();

        if ($this->notificacion->estado === EstadoNotificacion::Enviada) {
            return;
        }

        $turno = $this->notificacion->turno?->fresh();
        if ($turno && $turno->estado === EstadoTurno::Cancelado && $this->notificacion->tipo !== 'cancelacion') {
            // El turno se canceló mientras el aviso esperaba en la cola: ya no corresponde enviarlo
            $this->notificacion->delete();

            return;
        }

        $destinatario = $this->notificacion->destinatario;
        $destinatario->notify(new AvisoTurno($this->notificacion, $this->datos));

        $this->notificacion->update([
            'estado' => EstadoNotificacion::Enviada,
            'enviado_at' => now(),
        ]);

        try {
            $whatsApp->enviarAviso($this->notificacion, $this->datos);
        } catch (Throwable $e) {
            Log::warning('WhatsApp no enviado', [
                'notificacion_id' => $this->notificacion->id,
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->notificacion->update([
            'estado' => EstadoNotificacion::Fallida,
        ]);
    }
}
