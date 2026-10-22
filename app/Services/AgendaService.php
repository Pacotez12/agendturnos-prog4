<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Exceptions\HorarioNoDisponible;
use App\Exceptions\TransicionInvalida;
use App\Models\Notificacion;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AgendaService
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private NotificacionService $notificaciones
    ) {}

    public function solicitudesPendientes(User $user): Builder
    {
        $query = Turno::query()
            ->where('estado', EstadoTurno::Solicitado)
            ->where('inicio', '>=', now());

        if ($user->rol === RolUsuario::Profesional) {
            $query->where('profesional_id', $user->profesional?->id);
        }

        return $query;
    }

    public function confirmar(Turno $turno, User $usuario): Turno
    {
        $turno = $this->cambiarEstado($turno, EstadoTurno::Confirmado, $usuario);

        $this->notificaciones->programar($turno, 'confirmacion');

        return $turno;
    }

    public function cancelar(Turno $turno, ?string $motivo, User $usuario): Turno
    {
        $turno = $this->cambiarEstado($turno, EstadoTurno::Cancelado, $usuario, [
            'motivo_cancelacion' => $motivo,
        ]);

        $this->notificaciones->programar($turno, 'cancelacion');

        return $turno;
    }

    public function marcarAtendido(Turno $turno, User $usuario): Turno
    {
        return $this->cambiarEstado($turno, EstadoTurno::Atendido, $usuario);
    }

    public function marcarAusente(Turno $turno, User $usuario): Turno
    {
        return $this->cambiarEstado($turno, EstadoTurno::Ausente, $usuario);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function cambiarEstado(Turno $turno, EstadoTurno $nuevo, User $usuario, array $extra = []): Turno
    {
        if (! $turno->estado->puedePasarA($nuevo)) {
            $accion = match ($nuevo) {
                EstadoTurno::Confirmado => 'confirmar',
                EstadoTurno::Cancelado => 'cancelar',
                EstadoTurno::Atendido => 'marcar como atendido',
                EstadoTurno::Ausente => 'marcar como ausente',
                default => 'cambiar de estado',
            };
            throw new TransicionInvalida("No se puede {$accion} un turno en estado {$turno->estado->value}.");
        }

        if (in_array($nuevo, [EstadoTurno::Atendido, EstadoTurno::Ausente], true)) {
            $tz = config('app.timezone', 'America/Asuncion');
            if (Carbon::parse($turno->inicio)->setTimezone($tz)->gt(now($tz))) {
                $accion = $nuevo === EstadoTurno::Atendido ? 'marcar como atendido' : 'marcar como ausente';
                throw new TransicionInvalida("Solo se puede {$accion} un turno que ya haya iniciado.");
            }
        }

        return DB::transaction(function () use ($turno, $nuevo, $usuario, $extra) {
            $estadoAnterior = $turno->estado;
            $turno->estado = $nuevo;

            foreach ($extra as $key => $value) {
                $turno->{$key} = $value;
            }

            $turno->save();

            $turno->historial()->create([
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevo,
                'usuario_id' => $usuario->id,
            ]);

            return $turno;
        });
    }

    public function reprogramar(Turno $turno, CarbonInterface $nuevoInicio, User $usuario): Turno
    {
        if (! in_array($turno->estado, [EstadoTurno::Solicitado, EstadoTurno::Confirmado], true)) {
            throw new TransicionInvalida("No se puede reprogramar un turno en estado {$turno->estado->value}.");
        }

        $tz = config('app.timezone', 'America/Asuncion');
        $inicioCarbon = Carbon::parse($nuevoInicio)->setTimezone($tz);
        $finCarbon = $inicioCarbon->copy()->addMinutes((int) $turno->servicio->duracion_min);

        try {
            return DB::transaction(function () use ($turno, $inicioCarbon, $finCarbon, $usuario, $tz) {
                if (! $this->disponibilidad->estaLibre($turno->profesional, $turno->servicio, $inicioCarbon, $turno)) {
                    throw new HorarioNoDisponible('El horario seleccionado no está disponible.');
                }

                $consultorioId = $this->disponibilidad->consultorioPara($turno->profesional, $inicioCarbon);

                $inicioAnterior = Carbon::parse($turno->inicio)->setTimezone($tz);
                $detalle = sprintf(
                    'Reprogramado del %s al %s',
                    $inicioAnterior->format('d/m H:i'),
                    $inicioCarbon->format('d/m H:i')
                );

                $turno->inicio = $inicioCarbon;
                $turno->fin = $finCarbon;
                $turno->consultorio_id = $consultorioId;
                $turno->save();

                $turno->historial()->create([
                    'estado_anterior' => $turno->estado,
                    'estado_nuevo' => $turno->estado,
                    'usuario_id' => $usuario->id,
                    'detalle' => $detalle,
                ]);

                Notificacion::where('turno_id', $turno->id)->where('tipo', 'recordatorio')->delete();
                $this->notificaciones->programar($turno, 'reprogramacion', [
                    'anterior' => $inicioAnterior->toIso8601String(),
                ]);

                return $turno;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01' || str_contains($e->getMessage(), '23P01') || str_contains($e->getMessage(), 'turnos_sin_solapamiento')) {
                throw new HorarioNoDisponible('El horario seleccionado no está disponible.', 0, $e);
            }

            throw $e;
        }
    }
}
