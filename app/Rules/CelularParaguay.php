<?php

namespace App\Rules;

use App\Support\Telefono;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CelularParaguay implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || Telefono::normalizar($value) === null) {
            $fail('Ingresá un celular válido, por ejemplo 0981 123 456.');
        }
    }
}
