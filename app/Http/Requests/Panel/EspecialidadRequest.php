<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EspecialidadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'activa' => $this->boolean('activa'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $especialidad = $this->route('especialidad');
        $especialidadId = is_object($especialidad) ? $especialidad->id : $especialidad;

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('especialidades', 'nombre')->ignore($especialidadId),
            ],
            'activa' => ['boolean'],
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
            'nombre.required' => 'El nombre de la especialidad es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una especialidad con este nombre.',
        ];
    }
}
