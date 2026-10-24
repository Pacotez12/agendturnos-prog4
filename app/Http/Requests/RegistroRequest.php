<?php

namespace App\Http\Requests;

use App\Rules\CelularParaguay;
use App\Support\Telefono;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['required', 'string', Rule::in(['CI', 'pasaporte'])],
            'documento' => [
                'required',
                'string',
                'max:30',
                Rule::unique('pacientes', 'documento')->where(fn ($query) => $query->where('tipo_documento', $this->tipo_documento)),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'telefono' => ['nullable', 'string', new CelularParaguay],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'privacidad' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.max' => 'El apellido no puede tener más de 100 caracteres.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento debe ser CI o pasaporte.',
            'documento.required' => 'El número de documento es obligatorio.',
            'documento.max' => 'El número de documento no puede tener más de 30 caracteres.',
            'documento.unique' => 'Este número de documento ya está registrado para el tipo seleccionado.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser una fecha futura.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'privacidad.accepted' => 'Tenés que aceptar la política de privacidad para crear tu cuenta.',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if (is_array($validated) && ! empty($validated['telefono'])) {
            $validated['telefono'] = Telefono::normalizar($validated['telefono']);
        }

        return $validated;
    }
}
