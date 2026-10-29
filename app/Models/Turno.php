<?php

namespace App\Models;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos';

    protected $fillable = [
        'paciente_id',
        'profesional_id',
        'servicio_id',
        'consultorio_id',
        'inicio',
        'fin',
        'estado',
        'observaciones',
        'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoTurno::class,
            'inicio' => 'datetime',
            'fin' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialTurno::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    public function ultimoRecordatorio(): ?Notificacion
    {
        if ($this->relationLoaded('notificaciones')) {
            return $this->notificaciones
                ->where('tipo', 'recordatorio')
                ->whereNotNull('enviado_at')
                ->sortByDesc('enviado_at')
                ->first();
        }

        return $this->notificaciones()
            ->where('tipo', 'recordatorio')
            ->whereNotNull('enviado_at')
            ->latest('enviado_at')
            ->first();
    }

    public function minutosDesdeUltimoRecordatorio(): ?int
    {
        $ultimo = $this->ultimoRecordatorio();
        if (! $ultimo || ! $ultimo->enviado_at) {
            return null;
        }

        return (int) $ultimo->enviado_at->diffInMinutes(now());
    }

    public function puedeReenviarRecordatorio(): bool
    {
        if ($this->estado !== EstadoTurno::Confirmado || $this->inicio->isPast()) {
            return false;
        }

        $tienePendiente = $this->relationLoaded('notificaciones')
            ? $this->notificaciones->where('tipo', 'recordatorio')->where('estado', EstadoNotificacion::Pendiente)->isNotEmpty()
            : $this->notificaciones()->where('tipo', 'recordatorio')->where('estado', EstadoNotificacion::Pendiente)->exists();

        if ($tienePendiente) {
            return false;
        }

        $minutos = $this->minutosDesdeUltimoRecordatorio();

        return $minutos === null || $minutos >= 15;
    }
}
