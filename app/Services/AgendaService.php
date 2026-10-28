<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Exceptions\HorarioNoDisponible;
use App\Exceptions\PacienteConTurnoEnHorario;
use App\Exceptions\TransicionInvalida;
use App\Models\Disponibilidad;
use App\Models\Notificacion;
use App\Models\Profesional;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
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

    public function turnosPasadosSinMarcar(User $user): Builder
    {
        $ahora = now(config('app.timezone', 'America/Asuncion'));

        $query = Turno::query()
            ->where('estado', EstadoTurno::Confirmado)
            ->where('fin', '<', $ahora)
            ->where('inicio', '>=', $ahora->copy()->subDays(7));

        if ($user->rol === RolUsuario::Profesional) {
            $query->where('profesional_id', $user->profesional?->id);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function datosInicioPanel(User $user): array
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $ahora = now($tz);
        $hora = $ahora->hour;

        $saludo = match (true) {
            $hora < 5 => 'Buenas noches',
            $hora < 12 => 'Buen día',
            $hora < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        $fechaHoyTexto = ucfirst($ahora->translatedFormat('l j \d\e F'));

        $profesionalId = $user->rol === RolUsuario::Profesional ? $user->profesional?->id : null;

        // Indicador 1: Turnos hoy
        $queryHoy = Turno::query()
            ->whereDate('inicio', $ahora->toDateString())
            ->where('estado', '!=', EstadoTurno::Cancelado);

        if ($profesionalId) {
            $queryHoy->where('profesional_id', $profesionalId);
        }

        $turnosHoyCount = (clone $queryHoy)->count();
        $atendidosHoyCount = (clone $queryHoy)->where('estado', EstadoTurno::Atendido)->count();

        // Indicador 2: Por confirmar
        $solicitudesCount = $this->solicitudesPendientes($user)->count();

        // Indicador 3 y 4: Semana actual (Lunes a Domingo)
        $inicioSemana = $ahora->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $finSemana = $ahora->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $querySemana = Turno::query()
            ->whereBetween('inicio', [$inicioSemana, $finSemana]);

        if ($profesionalId) {
            $querySemana->where('profesional_id', $profesionalId);
        }

        $atendidosSemanaCount = (clone $querySemana)->where('estado', EstadoTurno::Atendido)->count();
        $confirmadosSemanaCount = (clone $querySemana)
            ->whereIn('estado', [EstadoTurno::Confirmado, EstadoTurno::Atendido, EstadoTurno::Ausente])
            ->count();
        $ausentesSemanaCount = (clone $querySemana)->where('estado', EstadoTurno::Ausente)->count();

        $totalResueltosSemana = $atendidosSemanaCount + $ausentesSemanaCount;
        $porcentajeInasistencia = $totalResueltosSemana > 0
            ? (int) round(($ausentesSemanaCount / $totalResueltosSemana) * 100)
            : 0;

        // Próximos de hoy
        $queryProximos = Turno::query()
            ->with(['servicio', 'paciente', 'profesional', 'notificaciones'])
            ->whereDate('inicio', $ahora->toDateString())
            ->where('fin', '>=', $ahora)
            ->where('estado', '!=', EstadoTurno::Cancelado)
            ->orderBy('inicio', 'asc');

        if ($profesionalId) {
            $queryProximos->where('profesional_id', $profesionalId);
        }

        $proximosDeHoy = $queryProximos->get();

        // Pendientes
        $turnosSinMarcarCount = $this->turnosPasadosSinMarcar($user)->count();

        return [
            'saludo' => $saludo,
            'nombreUsuario' => $user->nombreVisible(),
            'fechaHoyTexto' => $fechaHoyTexto,
            'esFuncionario' => $user->rol === RolUsuario::Funcionario,
            'indicadores' => [
                'turnosHoy' => $turnosHoyCount,
                'atendidosHoy' => $atendidosHoyCount,
                'solicitudes' => $solicitudesCount,
                'atendidosSemana' => $atendidosSemanaCount,
                'confirmadosSemana' => $confirmadosSemanaCount,
                'ausentesSemana' => $ausentesSemanaCount,
                'porcentajeInasistencia' => $porcentajeInasistencia,
            ],
            'proximosDeHoy' => $proximosDeHoy,
            'pendientes' => [
                'solicitudes' => $solicitudesCount,
                'sinMarcar' => $turnosSinMarcarCount,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosInicioPaciente(User $user): array
    {
        $pacientePropio = $user->paciente;
        $proximoPropio = null;

        if ($pacientePropio) {
            $proximoPropio = Turno::query()
                ->with(['servicio', 'profesional'])
                ->where('paciente_id', $pacientePropio->id)
                ->where('inicio', '>=', now())
                ->whereIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado])
                ->orderBy('inicio', 'asc')
                ->first();
        }

        $dependientes = $user->dependientes()->get();
        $proximosDependientes = [];

        foreach ($dependientes as $dep) {
            $proximoDep = Turno::query()
                ->with(['servicio', 'profesional'])
                ->where('paciente_id', $dep->id)
                ->where('inicio', '>=', now())
                ->whereIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado])
                ->orderBy('inicio', 'asc')
                ->first();

            if ($proximoDep) {
                $proximosDependientes[] = [
                    'paciente' => $dep,
                    'turno' => $proximoDep,
                ];
            }
        }

        return [
            'nombre' => $user->paciente?->nombre ?? $user->nombreVisible(),
            'proximoPropio' => $proximoPropio,
            'proximosDependientes' => $proximosDependientes,
            'tieneProximos' => $proximoPropio !== null || count($proximosDependientes) > 0,
        ];
    }

    /**
     * @return array{
     *     profesionales: Collection<int, Profesional>,
     *     turnosPorProfesional: Collection<int, Collection<int, Turno>>,
     *     horaMin: string,
     *     horaMax: string,
     *     franjas: array<int, string>
     * }
     */
    public function agendaDelDia(User $user, CarbonInterface $fecha, ?EstadoTurno $estado = null): array
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $fechaCarbon = Carbon::parse($fecha, $tz)->setTimezone($tz);
        $inicioDia = $fechaCarbon->copy()->startOfDay();
        $finDia = $fechaCarbon->copy()->endOfDay();

        $profesionales = $user->rol === RolUsuario::Profesional && $user->profesional
            ? collect([$user->profesional])
            : Profesional::ordenados()->get();

        $profesionalesIds = $profesionales->pluck('id')->all();

        $query = Turno::query()
            ->with(['servicio', 'paciente', 'profesional'])
            ->whereIn('profesional_id', $profesionalesIds)
            ->whereBetween('inicio', [$inicioDia, $finDia]);

        if ($estado) {
            $query->where('estado', $estado);
        } else {
            $query->where('estado', '!=', EstadoTurno::Cancelado);
        }

        $turnos = $query->orderBy('inicio', 'asc')->get();
        $turnosPorProfesional = $turnos->groupBy('profesional_id');

        $diaSemana = $fechaCarbon->dayOfWeekIso;
        $disponibilidades = Disponibilidad::whereIn('profesional_id', $profesionalesIds)
            ->where('dia_semana', $diaSemana)
            ->get();

        [$horaMin, $horaMax] = $this->calcularRangoHoras($disponibilidades, $turnos);
        $franjas = $this->generarFranjasHorarias($fechaCarbon, $horaMin, $horaMax);

        return [
            'profesionales' => $profesionales,
            'turnosPorProfesional' => $turnosPorProfesional,
            'horaMin' => $horaMin,
            'horaMax' => $horaMax,
            'franjas' => $franjas,
        ];
    }

    /**
     * @return array{
     *     profesional: Profesional,
     *     todosProfesionales: Collection<int, Profesional>,
     *     dias: array<int, Carbon>,
     *     turnosPorDia: Collection<string, Collection<int, Turno>>,
     *     horaMin: string,
     *     horaMax: string,
     *     franjas: array<int, string>
     * }
     */
    public function agendaDeLaSemana(User $user, ?Profesional $profesional, CarbonInterface $fecha, ?EstadoTurno $estado = null): array
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $fechaCarbon = Carbon::parse($fecha, $tz)->setTimezone($tz);
        $lunes = $fechaCarbon->copy()->startOfWeek(Carbon::MONDAY);

        $profesionalSeleccionado = $user->rol === RolUsuario::Profesional && $user->profesional
            ? $user->profesional
            : ($profesional ?? Profesional::ordenados()->first());

        $dias = [];
        for ($i = 0; $i < 5; $i++) {
            $dias[] = $lunes->copy()->addDays($i);
        }

        $sabado = $lunes->copy()->addDays(5);
        $domingo = $lunes->copy()->addDays(6);

        $hayDispSabado = Disponibilidad::where('profesional_id', $profesionalSeleccionado->id)
            ->where('dia_semana', 6)
            ->exists();
        $hayTurnosSabado = Turno::where('profesional_id', $profesionalSeleccionado->id)
            ->whereBetween('inicio', [$sabado->copy()->startOfDay(), $sabado->copy()->endOfDay()])
            ->when($estado, fn ($q) => $q->where('estado', $estado), fn ($q) => $q->where('estado', '!=', EstadoTurno::Cancelado))
            ->exists();

        $hayDispDomingo = Disponibilidad::where('profesional_id', $profesionalSeleccionado->id)
            ->where('dia_semana', 7)
            ->exists();
        $hayTurnosDomingo = Turno::where('profesional_id', $profesionalSeleccionado->id)
            ->whereBetween('inicio', [$domingo->copy()->startOfDay(), $domingo->copy()->endOfDay()])
            ->when($estado, fn ($q) => $q->where('estado', $estado), fn ($q) => $q->where('estado', '!=', EstadoTurno::Cancelado))
            ->exists();

        if ($hayDispSabado || $hayTurnosSabado || $hayDispDomingo || $hayTurnosDomingo) {
            $dias[] = $sabado;
        }

        if ($hayDispDomingo || $hayTurnosDomingo) {
            $dias[] = $domingo;
        }

        $finSemana = end($dias)->copy()->endOfDay();

        $query = Turno::query()
            ->with(['servicio', 'paciente', 'profesional'])
            ->where('profesional_id', $profesionalSeleccionado->id)
            ->whereBetween('inicio', [$lunes->copy()->startOfDay(), $finSemana]);

        if ($estado) {
            $query->where('estado', $estado);
        } else {
            $query->where('estado', '!=', EstadoTurno::Cancelado);
        }

        $turnos = $query->orderBy('inicio', 'asc')->get();
        $turnosPorDia = $turnos->groupBy(fn ($t) => $t->inicio->toDateString());

        $diasSemana = array_map(fn ($d) => (int) $d->dayOfWeekIso, $dias);
        $disponibilidades = Disponibilidad::where('profesional_id', $profesionalSeleccionado->id)
            ->whereIn('dia_semana', $diasSemana)
            ->get();

        [$horaMin, $horaMax] = $this->calcularRangoHoras($disponibilidades, $turnos);
        $franjas = $this->generarFranjasHorarias($lunes, $horaMin, $horaMax);

        $todosProfesionales = Profesional::ordenados()->get();

        return [
            'profesional' => $profesionalSeleccionado,
            'todosProfesionales' => $todosProfesionales,
            'dias' => $dias,
            'turnosPorDia' => $turnosPorDia,
            'horaMin' => $horaMin,
            'horaMax' => $horaMax,
            'franjas' => $franjas,
        ];
    }

    /**
     * @param  Collection<int, Disponibilidad>  $disponibilidades
     * @param  Collection<int, Turno>  $turnos
     * @return array{0: string, 1: string}
     */
    private function calcularRangoHoras($disponibilidades, $turnos): array
    {
        $horaMin = '08:00';
        $horaMax = '18:00';

        if ($disponibilidades->isNotEmpty()) {
            $minDisp = substr((string) $disponibilidades->min('hora_inicio'), 0, 5);
            $maxDisp = substr((string) $disponibilidades->max('hora_fin'), 0, 5);
            if ($minDisp) {
                $horaMin = $minDisp;
            }
            if ($maxDisp) {
                $horaMax = $maxDisp;
            }
        }

        if ($turnos->isNotEmpty()) {
            $minTurno = $turnos->min('inicio')->format('H:i');
            $maxTurno = $turnos->max('fin')->format('H:i');
            if ($minTurno < $horaMin) {
                $horaMin = $minTurno;
            }
            if ($maxTurno > $horaMax) {
                $horaMax = $maxTurno;
            }
        }

        $hMin = (int) substr($horaMin, 0, 2);
        $horaMin = sprintf('%02d:00', $hMin);

        $hMax = (int) substr($horaMax, 0, 2);
        $mMax = (int) substr($horaMax, 3, 2);
        if ($mMax > 0) {
            $hMax++;
        }
        $horaMax = sprintf('%02d:00', min(24, max($hMax, $hMin + 1)));

        return [$horaMin, $horaMax];
    }

    /**
     * @return array<int, string>
     */
    private function generarFranjasHorarias(CarbonInterface $referencia, string $horaMin, string $horaMax): array
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $actual = Carbon::parse($referencia->toDateString().' '.$horaMin, $tz);
        $limite = Carbon::parse($referencia->toDateString().' '.$horaMax, $tz);

        $franjas = [];
        while ($actual < $limite) {
            $franjas[] = $actual->format('H:i');
            $actual->addMinutes(30);
        }

        return $franjas;
    }

    public function turnosDelPaciente(User $user, bool $historial = false): Builder
    {
        $query = Turno::query()
            ->with(['servicio', 'profesional', 'paciente'])
            ->whereIn('paciente_id', $user->pacientesIds());

        if ($historial) {
            return $query
                ->where(function (Builder $q) {
                    $q->where('inicio', '<', now())
                        ->orWhereNotIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado]);
                })
                ->orderBy('inicio', 'desc');
        }

        return $this->filtrarProximos($query)->orderBy('inicio', 'asc');
    }

    public function proximosDelPaciente(User $user): Builder
    {
        return $this->turnosDelPaciente($user, historial: false);
    }

    public function conteoProximosDelPaciente(User $user): int
    {
        $query = Turno::query()->whereIn('paciente_id', $user->pacientesIds());

        return $this->filtrarProximos($query)->count();
    }

    private function filtrarProximos(Builder $query): Builder
    {
        return $query
            ->where('inicio', '>=', now())
            ->whereIn('estado', [EstadoTurno::Solicitado, EstadoTurno::Confirmado]);
    }

    public function confirmar(Turno $turno, User $usuario): Turno
    {
        $turno = $this->cambiarEstado($turno, EstadoTurno::Confirmado, $usuario);

        $this->notificaciones->programar($turno, 'confirmacion');

        return $turno;
    }

    public function puedeCancelarPaciente(Turno $turno): bool
    {
        if (! in_array($turno->estado, [EstadoTurno::Solicitado, EstadoTurno::Confirmado], true)) {
            return false;
        }

        $horasMinimas = (int) config('agenda.cancelacion_horas', 24);
        $tz = config('app.timezone', 'America/Asuncion');
        $limite = now($tz)->addHours($horasMinimas);
        $inicio = Carbon::parse($turno->inicio)->setTimezone($tz);

        return $inicio->gte($limite);
    }

    public function cancelar(
        Turno $turno,
        ?string $motivo,
        User $usuario,
        bool $notificar = true,
        ?string $detalle = null
    ): Turno {
        if ($usuario->rol === RolUsuario::Paciente && ! $this->puedeCancelarPaciente($turno)) {
            throw new TransicionInvalida('No se puede cancelar el turno con menos de '.config('agenda.cancelacion_horas', 24).' horas de anticipación.');
        }

        $turno = $this->cambiarEstado($turno, EstadoTurno::Cancelado, $usuario, [
            'motivo_cancelacion' => $motivo,
        ], $detalle);

        if ($notificar) {
            $this->notificaciones->programar($turno, 'cancelacion');
        }

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
    private function cambiarEstado(Turno $turno, EstadoTurno $nuevo, User $usuario, array $extra = [], ?string $detalle = null): Turno
    {
        return DB::transaction(function () use ($turno, $nuevo, $usuario, $extra, $detalle) {
            $turnoActual = Turno::where('id', $turno->id)->lockForUpdate()->firstOrFail();

            if (! $turnoActual->estado->puedePasarA($nuevo)) {
                $accion = match ($nuevo) {
                    EstadoTurno::Confirmado => 'confirmar',
                    EstadoTurno::Cancelado => 'cancelar',
                    EstadoTurno::Atendido => 'marcar como atendido',
                    EstadoTurno::Ausente => 'marcar como ausente',
                    default => 'cambiar de estado',
                };
                throw new TransicionInvalida("No se puede {$accion} un turno en estado {$turnoActual->estado->value}.");
            }

            if (in_array($nuevo, [EstadoTurno::Atendido, EstadoTurno::Ausente], true)) {
                $tz = config('app.timezone', 'America/Asuncion');
                if (Carbon::parse($turnoActual->inicio)->setTimezone($tz)->gt(now($tz))) {
                    $accion = $nuevo === EstadoTurno::Atendido ? 'marcar como atendido' : 'marcar como ausente';
                    throw new TransicionInvalida("Solo se puede {$accion} un turno que ya haya iniciado.");
                }
            }

            $estadoAnterior = $turnoActual->estado;
            $turnoActual->estado = $nuevo;

            foreach ($extra as $key => $value) {
                $turnoActual->{$key} = $value;
            }

            $turnoActual->save();

            $turnoActual->historial()->create([
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevo,
                'usuario_id' => $usuario->id,
                'detalle' => $detalle,
            ]);

            $turno->setRawAttributes($turnoActual->getAttributes(), true);

            return $turnoActual;
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

                if (! $this->disponibilidad->pacienteLibre($turno->paciente, $inicioCarbon, $finCarbon, $turno)) {
                    throw PacienteConTurnoEnHorario::para($turno->paciente);
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
            if ($choque = PacienteConTurnoEnHorario::siSolapamiento($e, $turno->paciente)) {
                throw $choque;
            }

            if ($horario = HorarioNoDisponible::siSolapamiento($e)) {
                throw $horario;
            }

            throw $e;
        }
    }
}
