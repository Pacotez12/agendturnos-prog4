<?php

namespace App\Http\Requests\Api\v1;

use App\Models\Profesional;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ConsultarDisponibilidadRequest extends FormRequest
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
            'profesional_id' => ['required', 'integer', 'exists:profesionales,id'],
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $tz = config('app.timezone', 'America/Asuncion');
                $fecha = Carbon::createFromFormat('Y-m-d', (string) $this->input('fecha'), $tz)->startOfDay();
                $hoy = Carbon::today($tz);

                if ($fecha->lt($hoy)) {
                    $validator->errors()->add('fecha', 'La fecha seleccionada no puede ser en el pasado.');
                }

                $servicio = Servicio::find($this->input('servicio_id'));
                if ($servicio && ! $servicio->activo) {
                    $validator->errors()->add('servicio_id', 'El servicio seleccionado no está disponible.');
                }

                $profesional = Profesional::find($this->input('profesional_id'));
                if ($profesional && $servicio && ! $profesional->servicios()->where('servicios.id', $servicio->id)->exists()) {
                    $validator->errors()->add('servicio_id', 'El profesional seleccionado no ofrece el servicio indicado.');
                }
            },
        ];
    }
}
