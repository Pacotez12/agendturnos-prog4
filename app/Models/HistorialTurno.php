<?php

namespace App\Models;

use App\Enums\EstadoTurno;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialTurno extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'historial_turnos';

    protected $fillable = [
        'turno_id',
        'estado_anterior',
        'estado_nuevo',
        'usuario_id',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoTurno::class,
            'estado_nuevo' => EstadoTurno::class,
        ];
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
