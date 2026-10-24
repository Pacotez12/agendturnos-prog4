<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Exceptions\HorarioNoDisponible;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private NotificacionService $notificaciones
    ) {}

    /**
     * Arma los datos del formulario de reserva según lo que se eligió hasta ahora.
     *
     * @return array{
     *     profesionales: Collection<int, Profesional>,
     *     profesional: ?Profesional,
     *     servicios: Collection<int, Servicio>,
     *     servicio: ?Servicio,
     *     fecha: ?Carbon,
     *     horarios: list<array{hora: string, libre: bool}>,
     * }
     */
    public function opciones(?int $profesionalId, ?int $servicioId, ?string $fecha): array
    {
        $profesionales = Profesional::ordenados()->get();
        $profesional = $profesionalId ? $profesionales->firstWhere('id', $profesionalId) : null;

        $servicios = $profesional
            ? $profesional->servicios()
                ->where('activo', true)
                ->whereHas('especialidad', fn ($q) => $q->where('activa', true))
                ->orderBy('nombre')
                ->get()
            : new Collection;
        $servicio = $servicioId ? $servicios->firstWhere('id', $servicioId) : null;

        $dia = ($servicio && $fecha) ? Carbon::parse($fecha, config('app.timezone')) : null;

        return [
            'profesionales' => $profesionales,
            'profesional' => $profesional,
            'servicios' => $servicios,
            'servicio' => $servicio,
            'fecha' => $dia,
            'horarios' => $dia ? $this->disponibilidad->franjasLibres($profesional, $servicio, $dia) : [],
        ];
    }

    public function solicitar(
        Profesional $profesional,
        Servicio $servicio,
        Paciente $paciente,
        CarbonInterface $inicio,
        User $usuario,
    ): Turno {
        return $this->crearTurno(
            $profesional,
            $servicio,
            $paciente,
            $inicio,
            $usuario,
            EstadoTurno::Solicitado,
            null
        );
    }

    public function agendar(
        Profesional $profesional,
        Servicio $servicio,
        Paciente $paciente,
        CarbonInterface $inicio,
        User $usuario,
    ): Turno {
        $turno = $this->crearTurno(
            $profesional,
            $servicio,
            $paciente,
            $inicio,
            $usuario,
            EstadoTurno::Confirmado,
            'Agendado por teléfono o en el consultorio'
        );

        $this->notificaciones->programar($turno, 'confirmacion');

        return $turno;
    }

    private function crearTurno(
        Profesional $profesional,
        Servicio $servicio,
        Paciente $paciente,
        CarbonInterface $inicio,
        User $usuario,
        EstadoTurno $estado,
        ?string $detalleHistorial
    ): Turno {
        $tz = config('app.timezone', 'America/Asuncion');
        $inicioCarbon = Carbon::parse($inicio)->setTimezone($tz);
        $finCarbon = $inicioCarbon->copy()->addMinutes((int) $servicio->duracion_min);

        try {
            return DB::transaction(function () use ($profesional, $servicio, $paciente, $inicioCarbon, $finCarbon, $usuario, $estado, $detalleHistorial) {
                if (! $this->disponibilidad->estaLibre($profesional, $servicio, $inicioCarbon)) {
                    throw new HorarioNoDisponible('El horario seleccionado no está disponible.');
                }

                $consultorioId = $this->disponibilidad->consultorioPara($profesional, $inicioCarbon);

                $turno = Turno::create([
                    'paciente_id' => $paciente->id,
                    'profesional_id' => $profesional->id,
                    'servicio_id' => $servicio->id,
                    'consultorio_id' => $consultorioId,
                    'inicio' => $inicioCarbon,
                    'fin' => $finCarbon,
                    'estado' => $estado,
                ]);

                $turno->historial()->create([
                    'estado_anterior' => null,
                    'estado_nuevo' => $estado,
                    'usuario_id' => $usuario->id,
                    'detalle' => $detalleHistorial,
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
