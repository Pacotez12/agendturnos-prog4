<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Turno;
use App\Support\Fecha;
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
        $plantilla = config('services.whatsapp.plantilla', 'aviso_turno');

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
        $esPropio = $turno->paciente->user_id === $destinatario->id;
        $nombrePaciente = trim("{$turno->paciente->nombre} {$turno->paciente->apellido}");

        $param1 = $destinatario->nombreVisible();
        $param2 = $this->armarFrase($notificacion->tipo, $esPropio, $nombrePaciente, $turno);
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

    private function armarFrase(string $tipo, bool $esPropio, string $nombrePaciente, Turno $turno): string
    {
        $cuando = Fecha::cuando($turno->inicio);
        // Meta rechaza parámetros con saltos de línea o espacios repetidos
        $motivo = rtrim(preg_replace('/\s+/', ' ', trim((string) $turno->motivo_cancelacion)), '.');

        if ($esPropio) {
            return match ($tipo) {
                'confirmacion' => 'Tu turno está confirmado.',
                'cancelacion' => $motivo !== ''
                    ? "Tu turno fue cancelado. Motivo: {$motivo}."
                    : 'Tu turno fue cancelado.',
                'reprogramacion' => 'Tu turno cambió de horario.',
                'recordatorio' => "Te recordamos tu turno de {$cuando}.",
                default => 'Tu turno fue actualizado.',
            };
        }

        return match ($tipo) {
            'confirmacion' => "El turno de {$nombrePaciente} está confirmado.",
            'cancelacion' => $motivo !== ''
                ? "El turno de {$nombrePaciente} fue cancelado. Motivo: {$motivo}."
                : "El turno de {$nombrePaciente} fue cancelado.",
            'reprogramacion' => "El turno de {$nombrePaciente} cambió de horario.",
            'recordatorio' => "Te recordamos el turno de {$nombrePaciente} de {$cuando}.",
            default => "El turno de {$nombrePaciente} fue actualizado.",
        };
    }
}
