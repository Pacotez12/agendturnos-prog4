<?php

namespace App\Http\Requests\Panel;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BloqueoRequest extends FormRequest
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
            'profesional_id' => [
                Rule::requiredIf(fn () => $this->user()?->rol === RolUsuario::Funcionario),
                'nullable',
                'integer',
                'exists:profesionales,id',
            ],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after:desde'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'confirmar' => ['sometimes', 'nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profesional_id.required' => 'Elegí una profesional.',
            'profesional_id.exists' => 'La profesional seleccionada no existe.',
        ];
    }
}
