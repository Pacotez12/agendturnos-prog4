<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolUsuario;
use App\Notifications\RestablecerContrasena;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['email', 'password', 'rol'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => RolUsuario::class,
        ];
    }

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class);
    }

    public function profesional(): HasOne
    {
        return $this->hasOne(Profesional::class);
    }

    public function dependientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'responsable_id');
    }

    /**
     * @return array<int, int>
     */
    public function pacientesIds(): array
    {
        return Paciente::query()
            ->where('user_id', $this->id)
            ->orWhere('responsable_id', $this->id)
            ->pluck('id')
            ->all();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasena($token));
    }

    public function nombreVisible(): string
    {
        if ($this->paciente) {
            return trim("{$this->paciente->nombre} {$this->paciente->apellido}");
        }

        if ($this->profesional) {
            return trim("{$this->profesional->nombre} {$this->profesional->apellido}");
        }

        if ($this->rol === RolUsuario::Funcionario) {
            return 'Recepción';
        }

        return 'Sistema';
    }
}
