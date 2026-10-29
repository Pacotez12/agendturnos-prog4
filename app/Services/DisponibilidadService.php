<?php

namespace App\Services;

use App\Enums\EstadoTurno;
use App\Models\Disponibilidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class DisponibilidadService
{
    /**
     * Devuelve las franjas horarias con su disponibilidad para un profesional, servicio y fecha.
     *
     * @return list<array{hora: string, libre: bool}>
     */
    public function franjasLibres(Profesional $profesional, Servicio $servicio, CarbonInterface $fecha, ?Turno $ignorar = null): array
    {
        if (! $this->profesionalOfreceServicio($profesional, $servicio)) {
            return [];
        }

        $tz = config('app.timezone', 'America/Asuncion');
        $fechaCarbon = Carbon::parse($fecha)->setTimezone($tz);
        $diaSemanaIso = $fechaCarbon->dayOfWeekIso;

        /** @var Collection<int, Disponibilidad> $disponibilidades */
        $disponibilidades = $profesional->disponibilidades()
            ->where('dia_semana', $diaSemanaIso)
            ->orderBy('hora_inicio')
            ->get();

        if ($disponibilidades->isEmpty()) {
            return [];
        }

        $intervalo = (int) config('agenda.intervalo_minutos', 15);
        $duracion = (int) $servicio->duracion_min;
        $ahora = Carbon::now($tz);

        $inicioDia = $fechaCarbon->copy()->startOfDay();
        $finDia = $fechaCarbon->copy()->endOfDay();

        $turnosQuery = $profesional->turnos()
            ->where('estado', '!=', EstadoTurno::Cancelado)
            ->where('inicio', '<', $finDia)
            ->where('fin', '>', $inicioDia);

        if ($ignorar) {
            $turnosQuery->where('id', '!=', $ignorar->id);
        }

        $turnos = $turnosQuery->get();

        $bloqueos = $profesional->bloqueos()
            ->where('inicio', '<', $finDia)
            ->where('fin', '>', $inicioDia)
            ->get();

        $candidatos = [];

        foreach ($disponibilidades as $disp) {
            $inicioFranja = Carbon::parse($fechaCarbon->toDateString().' '.$disp->hora_inicio, $tz);
            $finFranja = Carbon::parse($fechaCarbon->toDateString().' '.$disp->hora_fin, $tz);

            $puntero = $inicioFranja->copy();

            while ($puntero->copy()->addMinutes($duracion)->lte($finFranja)) {
                if ($puntero->lte($ahora)) {
                    $puntero->addMinutes($intervalo);

                    continue;
                }

                $slotInicio = $puntero->copy();
                $slotFin = $puntero->copy()->addMinutes($duracion);

                $chocaTurno = $turnos->contains(function ($turno) use ($slotInicio, $slotFin, $tz) {
                    $tInicio = Carbon::parse($turno->inicio)->setTimezone($tz);
                    $tFin = Carbon::parse($turno->fin)->setTimezone($tz);

                    return $slotInicio->lt($tFin) && $slotFin->gt($tInicio);
                });

                $chocaBloqueo = $bloqueos->contains(function ($bloqueo) use ($slotInicio, $slotFin, $disp, $tz) {
                    $aplicaConsultorio = is_null($bloqueo->consultorio_id) || $bloqueo->consultorio_id === $disp->consultorio_id;
                    $bInicio = Carbon::parse($bloqueo->inicio)->setTimezone($tz);
                    $bFin = Carbon::parse($bloqueo->fin)->setTimezone($tz);

                    return $aplicaConsultorio && $slotInicio->lt($bFin) && $slotFin->gt($bInicio);
                });

                $libre = ! $chocaTurno && ! $chocaBloqueo;
                $horaStr = $puntero->format('H:i');

                if (! isset($candidatos[$horaStr])) {
                    $candidatos[$horaStr] = [
                        'hora' => $horaStr,
                        'libre' => $libre,
                    ];
                } else {
                    $candidatos[$horaStr]['libre'] = $candidatos[$horaStr]['libre'] || $libre;
                }

                $puntero->addMinutes($intervalo);
            }
        }

        ksort($candidatos);

        return array_values($candidatos);
    }

    /**
     * Determina si un profesional está libre para iniciar un servicio en una fecha y hora exacta.
     */
    public function estaLibre(Profesional $profesional, Servicio $servicio, CarbonInterface $inicio, ?Turno $ignorar = null): bool
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $inicioCarbon = Carbon::parse($inicio)->setTimezone($tz);

        if ($inicioCarbon->lte(Carbon::now($tz))) {
            return false;
        }

        if (! $this->profesionalOfreceServicio($profesional, $servicio)) {
            return false;
        }

        $duracion = (int) $servicio->duracion_min;
        $finCarbon = $inicioCarbon->copy()->addMinutes($duracion);
        $diaSemanaIso = $inicioCarbon->dayOfWeekIso;

        $disp = $profesional->disponibilidades()
            ->where('dia_semana', $diaSemanaIso)
            ->whereTime('hora_inicio', '<=', $inicioCarbon->format('H:i:s'))
            ->whereTime('hora_fin', '>=', $finCarbon->format('H:i:s'))
            ->first();

        if (! $disp) {
            return false;
        }

        $inicioFranja = Carbon::parse($inicioCarbon->toDateString().' '.$disp->hora_inicio, $tz);
        $intervalo = (int) config('agenda.intervalo_minutos', 15);
        if ($intervalo > 0 && ($inicioFranja->diffInMinutes($inicioCarbon) % $intervalo !== 0 || $inicioCarbon->second !== 0)) {
            return false;
        }

        $chocaTurnoQuery = $profesional->turnos()
            ->where('estado', '!=', EstadoTurno::Cancelado)
            ->where('inicio', '<', $finCarbon)
            ->where('fin', '>', $inicioCarbon);

        if ($ignorar) {
            $chocaTurnoQuery->where('id', '!=', $ignorar->id);
        }

        $chocaTurno = $chocaTurnoQuery->exists();

        if ($chocaTurno) {
            return false;
        }

        $chocaBloqueo = $profesional->bloqueos()
            ->where(function ($q) use ($disp) {
                $q->whereNull('consultorio_id')
                    ->orWhere('consultorio_id', $disp->consultorio_id);
            })
            ->where('inicio', '<', $finCarbon)
            ->where('fin', '>', $inicioCarbon)
            ->exists();

        return ! $chocaBloqueo;
    }

    /**
     * Indica si el paciente no tiene otro turno vigente que se superponga con el rango dado.
     */
    public function pacienteLibre(Paciente $paciente, CarbonInterface $inicio, CarbonInterface $fin, ?Turno $ignorar = null): bool
    {
        return ! Turno::where('paciente_id', $paciente->id)
            ->where('estado', '!=', EstadoTurno::Cancelado)
            ->where('inicio', '<', $fin)
            ->where('fin', '>', $inicio)
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar->id))
            ->exists();
    }

    /**
     * Devuelve el consultorio asignado a un inicio de turno según la disponibilidad del profesional.
     */
    public function consultorioPara(Profesional $profesional, CarbonInterface $inicio): ?int
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $inicioCarbon = Carbon::parse($inicio)->setTimezone($tz);
        $diaSemanaIso = $inicioCarbon->dayOfWeekIso;

        $disp = $profesional->disponibilidades()
            ->where('dia_semana', $diaSemanaIso)
            ->whereTime('hora_inicio', '<=', $inicioCarbon->format('H:i:s'))
            ->whereTime('hora_fin', '>=', $inicioCarbon->format('H:i:s'))
            ->first();

        return $disp?->consultorio_id;
    }

    private function profesionalOfreceServicio(Profesional $profesional, Servicio $servicio): bool
    {
        return Profesional::activas()->whereKey($profesional->id)->exists() && $profesional->servicios()
            ->where('servicios.id', $servicio->id)
            ->where('servicios.activo', true)
            ->whereHas('especialidad', fn ($q) => $q->where('activa', true))
            ->exists();
    }
}
