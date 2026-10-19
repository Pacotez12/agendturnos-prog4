<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

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
            'profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesionales,id'],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after:desde'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'confirmar' => ['sometimes', 'nullable'],
        ];
    }
}
