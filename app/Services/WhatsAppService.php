<?php

namespace App\Services;

use App\Models\Notificacion;
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
        $plantillas = config('services.whatsapp.plantillas', []);
        $plantilla = $plantillas[$notificacion->tipo] ?? null;

        if (empty($token) || empty($phoneId) || empty($plantilla)) {
            return;
        }

        $destinatario = $notificacion->destinatario;
        $telefono = $destinatario?->paciente?->telefono;
        $telefonoNormalizado = $this->normalizarTelefono($telefono);

        if (empty($telefonoNormalizado)) {
            return;
        }

        $turno = $notificacion->turno;

        $param1 = $destinatario->nombreVisible();
        $param2 = trim("{$turno->paciente->nombre} {$turno->paciente->apellido}");
        $param3 = ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i'));
        $param4 = "{$turno->profesional->nombre} {$turno->profesional->apellido}";
        $param5 = $turno->servicio->nombre;

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
                        'parameters' => [
                            ['type' => 'text', 'text' => $param1],
                            ['type' => 'text', 'text' => $param2],
                            ['type' => 'text', 'text' => $param3],
                            ['type' => 'text', 'text' => $param4],
                            ['type' => 'text', 'text' => $param5],
                        ],
                    ],
                ],
            ],
        ];

        Http::withToken($token)
            ->post("https://graph.facebook.com/v25.0/{$phoneId}/messages", $payload)
            ->throw();
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
