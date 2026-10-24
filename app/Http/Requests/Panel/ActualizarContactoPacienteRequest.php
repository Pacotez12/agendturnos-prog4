<?php

namespace App\Http\Requests\Panel;

use App\Models\Paciente;
use App\Rules\CelularParaguay;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarContactoPacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Paciente|null $paciente */
        $paciente = $this->route('paciente');

        return $paciente && $this->user()?->can('update', $paciente);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'telefono' => ['nullable', 'string', new CelularParaguay],
        ];
    }
}
