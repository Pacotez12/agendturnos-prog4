<?php

namespace App\Http\Requests;

use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarFamiliarRequest extends FormRequest
{
    public const MENSAJE_DOCUMENTO_NO_DISPONIBLE = 'No pudimos agregar a esa persona con esos datos. Escribinos por WhatsApp y lo resolvemos.';

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
            'documento' => ['required', 'string', 'max:30'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $familiar = $this->route('paciente');

                $existe = Paciente::query()
                    ->where('tipo_documento', $this->input('tipo_documento'))
                    ->where('documento', $this->input('documento'))
                    ->when($familiar instanceof Paciente, fn ($q) => $q->whereKeyNot($familiar->id))
                    ->exists();

                if ($existe) {
                    $validator->errors()->add('documento', self::MENSAJE_DOCUMENTO_NO_DISPONIBLE);
                }
            },
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
            'fecha_nacimiento.required' => 'Elegí la fecha de nacimiento.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ];
    }
}
