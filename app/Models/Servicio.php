<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    protected $fillable = [
        'especialidad_id',
        'nombre',
        'descripcion',
        'duracion_min',
        'precio',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'duracion_min' => 'integer',
            'precio' => 'integer',
        ];
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(Profesional::class, 'profesional_servicio')->withTimestamps();
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }
}
