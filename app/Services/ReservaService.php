<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Exceptions\HorarioNoDisponible;
use App\Exceptions\PacienteConTurnoEnHorario;
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
use Illuminate\Validation\ValidationException;

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
        $profesionales = Profesional::activas()->ordenados()->get();
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
            null,
            validarLimiteSolicitudes: true
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
        ?string $detalleHistorial,
        bool $validarLimiteSolicitudes = false
    ): Turno {
        $tz = config('app.timezone', 'America/Asuncion');
        $inicioCarbon = Carbon::parse($inicio)->setTimezone($tz);
        $finCarbon = $inicioCarbon->copy()->addMinutes((int) $servicio->duracion_min);

        try {
            return DB::transaction(function () use ($profesional, $servicio, $paciente, $inicioCarbon, $finCarbon, $usuario, $estado, $detalleHistorial, $validarLimiteSolicitudes) {
                $profesional = Profesional::whereKey($profesional->id)->lockForUpdate()->firstOrFail();

                if ($validarLimiteSolicitudes) {
                    User::where('id', $usuario->id)->lockForUpdate()->first();

                    $max = (int) config('agenda.max_solicitudes_pendientes', 2);
                    $solicitudesFuturas = Turno::whereIn('paciente_id', $usuario->pacientesIds())
                        ->where('estado', EstadoTurno::Solicitado)
                        ->where('inicio', '>', now())
                        ->count();

                    if ($solicitudesFuturas >= $max) {
                        throw ValidationException::withMessages([
                            'solicitud' => "Ya tenés {$max} solicitudes esperando confirmación. Cuando la clínica las confirme vas a poder pedir otra.",
                        ]);
                    }
                }

                if (! $this->disponibilidad->estaLibre($profesional, $servicio, $inicioCarbon)) {
                    throw new HorarioNoDisponible('El horario seleccionado no está disponible.');
                }

                if (! $this->disponibilidad->pacienteLibre($paciente, $inicioCarbon, $finCarbon)) {
                    throw PacienteConTurnoEnHorario::para($paciente);
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
            if ($choque = PacienteConTurnoEnHorario::siSolapamiento($e, $paciente)) {
                throw $choque;
            }

            if ($horario = HorarioNoDisponible::siSolapamiento($e)) {
                throw $horario;
            }

            throw $e;
        }
    }
}
