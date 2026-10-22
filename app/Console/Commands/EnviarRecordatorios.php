<?php

namespace App\Console\Commands;

use App\Enums\EstadoTurno;
use App\Models\Turno;
use App\Services\NotificacionService;
use Illuminate\Console\Command;

class EnviarRecordatorios extends Command
{
    protected $signature = 'turnos:recordatorios';

    protected $description = 'Programa recordatorios para turnos confirmados entre 1h y 24h a partir de ahora';

    public function handle(NotificacionService $notificaciones): int
    {
        $ahora = now();
        $desde = $ahora->copy()->addHour();
        $hasta = $ahora->copy()->addHours(24);

        $turnos = Turno::where('estado', EstadoTurno::Confirmado)
            ->whereBetween('inicio', [$desde, $hasta])
            ->whereDoesntHave('notificaciones', function ($q) {
                $q->where('tipo', 'recordatorio');
            })
            ->get();

        foreach ($turnos as $turno) {
            $notificaciones->programar($turno, 'recordatorio');
        }

        $this->info("Se programaron {$turnos->count()} recordatorios.");

        return self::SUCCESS;
    }
}
