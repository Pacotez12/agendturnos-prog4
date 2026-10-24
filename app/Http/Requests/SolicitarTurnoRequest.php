<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitarTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return list<int>
     */
    public function pacientesPermitidos(): array
    {
        $user = $this->user();

        return $user ? $user->pacientesIds() : [];
    }

    public function rules(): array
    {
        return [
            'profesional' => ['required', 'integer', 'exists:profesionales,id'],
            'servicio' => [
                'required',
                'integer',
                Rule::exists('profesional_servicio', 'servicio_id')
                    ->where('profesional_id', (int) $this->input('profesional')),
                Rule::exists('servicios', 'id')
                    ->where('activo', true)
                    ->where(fn ($query) => $query->whereIn(
                        'especialidad_id',
                        fn ($sub) => $sub->select('id')->from('especialidades')->where('activa', true)
                    )),
            ],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'paciente_id' => [
                'required',
                'integer',
                Rule::in($this->pacientesPermitidos()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'profesional.required' => 'Elegí una profesional.',
            'profesional.exists' => 'La profesional elegida no existe.',
            'servicio.required' => 'Elegí un servicio.',
            'servicio.exists' => 'La profesional elegida no ofrece ese servicio.',
            'fecha.required' => 'Elegí una fecha.',
            'fecha.date_format' => 'La fecha no es válida.',
            'fecha.after_or_equal' => 'No se pueden reservar turnos en fechas pasadas.',
            'hora.required' => 'Elegí un horario.',
            'hora.date_format' => 'El horario no es válido.',
            'paciente_id.required' => 'Elegí para quién es el turno.',
            'paciente_id.in' => 'El paciente elegido no es válido.',
        ];
    }
}
