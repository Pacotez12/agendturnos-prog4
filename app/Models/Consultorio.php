<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultorio extends Model
{
    use HasFactory;

    protected $table = 'consultorios';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidad::class);
    }

    public function bloqueos(): HasMany
    {
        return $this->hasMany(Bloqueo::class);
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }
}
