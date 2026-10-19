<?php

namespace App\Http\Requests\Panel;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HorarioSemanalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('franjas') && is_array($this->franjas)) {
            $this->merge([
                'franjas' => array_values($this->franjas),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesionales,id'],
            'franjas' => ['present', 'array'],
            'franjas.*.dia_semana' => ['required', 'integer', 'between:1,7'],
            'franjas.*.hora_inicio' => ['required', 'date_format:H:i'],
            'franjas.*.hora_fin' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $franjas = $this->input('franjas', []);
            if (! is_array($franjas)) {
                return;
            }

            $porDia = [];

            foreach ($franjas as $i => $franja) {
                if (! isset($franja['hora_inicio'], $franja['hora_fin'], $franja['dia_semana'])) {
                    continue;
                }

                $inicio = $franja['hora_inicio'];
                $fin = $franja['hora_fin'];
                $dia = (int) $franja['dia_semana'];

                if ($fin <= $inicio) {
                    $v->errors()->add("franjas.{$i}.hora_fin", 'La hora de fin debe ser posterior a la de inicio.');

                    continue;
                }

                $porDia[$dia][] = [
                    'indice' => $i,
                    'inicio' => Carbon::createFromFormat('H:i', $inicio),
                    'fin' => Carbon::createFromFormat('H:i', $fin),
                ];
            }

            foreach ($porDia as $dia => $franjasDia) {
                $count = count($franjasDia);
                for ($j = 0; $j < $count; $j++) {
                    for ($k = $j + 1; $k < $count; $k++) {
                        $f1 = $franjasDia[$j];
                        $f2 = $franjasDia[$k];

                        // Hay solapamiento si f1.inicio < f2.fin && f1.fin > f2.inicio
                        if ($f1['inicio']->lt($f2['fin']) && $f1['fin']->gt($f2['inicio'])) {
                            $v->errors()->add('franjas', 'Las franjas de un mismo día no pueden superponerse.');
                            break 2;
                        }
                    }
                }
            }
        });
    }
}
