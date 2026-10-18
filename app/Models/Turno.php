<?php

namespace App\Models;

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
}
