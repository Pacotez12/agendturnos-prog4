<?php

namespace App\Http\Requests\Panel;

use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarPacienteOperadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                Rule::unique('pacientes', 'documento')->where(fn ($q) => $q->where('tipo_documento', $this->tipo_documento)),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'responsable_documento' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documento.unique' => 'Ya existe un paciente con ese documento.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $docResponsable = $this->input('responsable_documento');
            if ($docResponsable) {
                $responsable = Paciente::where('documento', $docResponsable)->first();
                if (! $responsable || ! $responsable->user_id) {
                    $validator->errors()->add(
                        'responsable_documento',
                        'El documento del responsable debe pertenecer a un paciente con cuenta activa.'
                    );
                }
            }
        });
    }
}
