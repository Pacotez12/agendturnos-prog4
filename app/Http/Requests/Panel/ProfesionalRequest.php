<?php

namespace App\Http\Requests\Panel;

use App\Enums\RolUsuario;
use App\Models\Profesional;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfesionalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->rol === RolUsuario::Funcionario;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Profesional|null $profesional */
        $profesional = $this->route('profesional');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'matricula' => [
                'required',
                'string',
                'max:30',
                Rule::unique('profesionales', 'matricula')->ignore($profesional?->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($profesional?->user_id),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribí el nombre de la profesional.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'apellido.required' => 'Escribí el apellido de la profesional.',
            'apellido.max' => 'El apellido no puede superar los 100 caracteres.',
            'matricula.required' => 'Escribí el número de matrícula.',
            'matricula.max' => 'La matrícula no puede superar los 30 caracteres.',
            'matricula.unique' => 'Ya existe una profesional registrada con esa matrícula.',
            'email.required' => 'Escribí el correo electrónico.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
        ];
    }
}
