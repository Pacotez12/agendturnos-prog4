<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\User;
use App\Support\Telefono;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function enviarAviso(Notificacion $notificacion, array $datos = []): void
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        if (empty($token) || empty($phoneId)) {
            return;
        }

        $destinatario = $notificacion->destinatario;
        $telefono = $destinatario?->paciente?->telefono;
        $telefonoNormalizado = $this->normalizarTelefono($telefono);

        if (empty($telefonoNormalizado)) {
            return;
        }

        $turno = $notificacion->turno;

        $plantillasComunes = config('services.whatsapp.plantillas', []);
        $plantillasPropio = config('services.whatsapp.plantillas_propio', []);

        $esPropio = $destinatario !== null
            && $turno->paciente?->user_id !== null
            && $turno->paciente->user_id === $destinatario->id;

        $plantillaPropio = $esPropio ? ($plantillasPropio[$notificacion->tipo] ?? null) : null;

        $primerNombre = $this->primerNombreDestinatario($destinatario);
        $fechaHora = ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i'));
        $profesional = "{$turno->profesional->nombre} {$turno->profesional->apellido}";
        $servicio = $turno->servicio->nombre;

        if (! empty($plantillaPropio)) {
            $plantilla = $plantillaPropio;
            $parameters = [
                ['type' => 'text', 'text' => $primerNombre],
                ['type' => 'text', 'text' => $fechaHora],
                ['type' => 'text', 'text' => $profesional],
                ['type' => 'text', 'text' => $servicio],
            ];
        } else {
            $plantilla = $plantillasComunes[$notificacion->tipo] ?? null;
            if (empty($plantilla)) {
                return;
            }

            $nombrePaciente = trim("{$turno->paciente->nombre} {$turno->paciente->apellido}");
            $parameters = [
                ['type' => 'text', 'text' => $primerNombre],
                ['type' => 'text', 'text' => $nombrePaciente],
                ['type' => 'text', 'text' => $fechaHora],
                ['type' => 'text', 'text' => $profesional],
                ['type' => 'text', 'text' => $servicio],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefonoNormalizado,
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => ['code' => 'es'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $parameters,
                    ],
                ],
            ],
        ];

        Http::withToken($token)
            ->post("https://graph.facebook.com/v25.0/{$phoneId}/messages", $payload)
            ->throw();
    }

    public function primerNombreDestinatario(?User $destinatario): string
    {
        $base = $destinatario?->paciente?->nombre ?: ($destinatario?->nombreVisible() ?? '');

        return preg_split('/\s+/u', trim($base))[0] ?? '';
    }

    public function normalizarTelefono(?string $telefono): ?string
    {
        $normalizado = Telefono::normalizar($telefono);
        if (! $normalizado) {
            return null;
        }

        return '595'.substr($normalizado, 1);
    }
}
