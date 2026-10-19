<?php

namespace App\Http\Requests\Panel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ServicioRequest extends FormRequest
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
            'activo' => $this->boolean('activo'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'especialidad_id' => ['required', 'exists:especialidades,id'],
            'duracion_min' => ['required', 'integer', 'min:5', 'max:480'],
            'precio' => ['nullable', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
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
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'especialidad_id.required' => 'La especialidad es obligatoria.',
            'especialidad_id.exists' => 'La especialidad seleccionada no es válida.',
            'duracion_min.required' => 'La duración es obligatoria.',
            'duracion_min.integer' => 'La duración debe ser un número entero.',
            'duracion_min.min' => 'La duración mínima es de 5 minutos.',
            'duracion_min.max' => 'La duración máxima es de 480 minutos.',
            'precio.integer' => 'El precio debe ser un número entero.',
            'precio.min' => 'El precio no puede ser negativo.',
            'descripcion.max' => 'La descripción no puede superar los 500 caracteres.',
        ];
    }
}
