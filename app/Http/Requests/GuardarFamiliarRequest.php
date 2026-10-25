<?php

namespace App\Http\Requests;

use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarFamiliarRequest extends FormRequest
{
    /**
     * Al editar, solo el responsable del familiar puede hacerlo.
     */
    public function authorize(): bool
    {
        $familiar = $this->route('paciente');

        if (! $familiar instanceof Paciente) {
            return true;
        }

        return $familiar->user_id === null && $familiar->responsable_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
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
                Rule::unique('pacientes', 'documento')
                    ->where(fn ($q) => $q->where('tipo_documento', $this->tipo_documento))
                    ->ignore($this->route('paciente')),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribí el nombre.',
            'apellido.required' => 'Escribí el apellido.',
            'tipo_documento.required' => 'Elegí el tipo de documento.',
            'tipo_documento.in' => 'El tipo de documento debe ser CI o pasaporte.',
            'documento.required' => 'Escribí el número de documento.',
            'documento.unique' => 'Esa persona ya está registrada en la clínica. Escribinos por WhatsApp y la vinculamos a tu cuenta.',
            'fecha_nacimiento.required' => 'Elegí la fecha de nacimiento.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ];
    }
}
