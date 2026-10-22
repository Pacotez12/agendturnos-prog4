<?php

namespace App\Http\Requests\Panel;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendarTurnoOperadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user?->rol === RolUsuario::Profesional && $user->profesional) {
            $this->merge([
                'profesional' => $user->profesional->id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Elegí un paciente.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'profesional.required' => 'Elegí una profesional.',
            'profesional.exists' => 'La profesional seleccionada no existe.',
            'servicio.required' => 'Elegí un servicio.',
            'servicio.exists' => 'El servicio seleccionado no está disponible para esta profesional.',
            'fecha.required' => 'Elegí una fecha.',
            'fecha.date_format' => 'La fecha no tiene un formato válido.',
            'fecha.after_or_equal' => 'La fecha no puede ser en el pasado.',
            'hora.required' => 'Elegí un horario.',
            'hora.date_format' => 'El horario no tiene un formato válido.',
        ];
    }
}
