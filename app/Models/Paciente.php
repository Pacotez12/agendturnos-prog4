<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = [
        'user_id',
        'responsable_id',
        'nombre',
        'apellido',
        'tipo_documento',
        'documento',
        'telefono',
        'fecha_nacimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function dependientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'responsable_id', 'user_id');
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    /**
     * @param  Builder<Paciente>  $query
     * @return Builder<Paciente>
     */
    public function scopeBuscar(Builder $query, string $texto, ?int $limit = null): Builder
    {
        $escaped = addcslashes($texto, '%_\\');

        $qNorm = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            mb_strtolower($escaped)
        );
        $normalize = fn (string $col): string => "translate(lower({$col}), 'áéíóúüñ', 'aeiouun')";

        $query = $query
            ->where(function (Builder $q) use ($escaped, $qNorm, $normalize) {
                $q->whereRaw("{$normalize('nombre')} LIKE ?", ["%{$qNorm}%"])
                    ->orWhereRaw("{$normalize('apellido')} LIKE ?", ["%{$qNorm}%"])
                    ->orWhere('documento', 'ILIKE', "%{$escaped}%");
            })
            ->orderBy('apellido')
            ->orderBy('nombre');

        return $limit !== null ? $query->limit($limit) : $query;
    }
}
