<?php

namespace App\Models;

use App\Enums\EstadoNotificacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'destinatario_id',
        'turno_id',
        'tipo',
        'canal',
        'estado',
        'enviado_at',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoNotificacion::class,
            'enviado_at' => 'datetime',
        ];
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }
}
