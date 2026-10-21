<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultaHorariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profesional' => ['nullable', 'integer', 'required_with:servicio', 'exists:profesionales,id'],
            'servicio' => [
                'nullable',
                'integer',
                'required_with:fecha',
                Rule::exists('profesional_servicio', 'servicio_id')
                    ->where('profesional_id', (int) $this->input('profesional')),
                Rule::exists('servicios', 'id')->where('activo', true),
            ],
            'fecha' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'profesional.required_with' => 'Elegí una profesional antes de elegir el servicio.',
            'profesional.exists' => 'La profesional elegida no existe.',
            'servicio.required_with' => 'Elegí un servicio antes de elegir el día.',
            'servicio.exists' => 'La profesional elegida no ofrece ese servicio.',
            'fecha.date_format' => 'La fecha no es válida.',
            'fecha.after_or_equal' => 'No se pueden reservar turnos en fechas pasadas.',
        ];
    }
}
