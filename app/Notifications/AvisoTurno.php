<?php

namespace App\Notifications;

use App\Models\Notificacion;
use App\Support\Fecha;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvisoTurno extends Notification
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function __construct(
        public Notificacion $notificacion,
        public array $datos = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $turno = $this->notificacion->turno;
        $tipo = $this->notificacion->tipo;

        $cuando = $turno->inicio->isToday() ? 'hoy' : 'mañana';

        $subject = match ($tipo) {
            'confirmacion' => 'Tu turno está confirmado · '.Fecha::corta($turno->inicio).', '.$turno->inicio->format('H:i'),
            'cancelacion' => 'Tu turno fue cancelado · '.Fecha::corta($turno->inicio).', '.$turno->inicio->format('H:i'),
            'reprogramacion' => 'Tu turno cambió de horario · '.Fecha::corta($turno->inicio).', '.$turno->inicio->format('H:i'),
            'recordatorio' => "Recordatorio: {$cuando} a las ".$turno->inicio->format('H:i').' en Glow Dental',
            default => 'Aviso sobre tu turno · Glow Dental',
        };

        $whatsappRaw = (string) config('services.whatsapp.numero', '595982774586');
        if (preg_match('/^(\+?595)(\d{3})(\d{6})$/', $whatsappRaw, $m)) {
            $whatsappFormateado = '+595 '.$m[2].' '.$m[3];
        } else {
            $whatsappFormateado = $whatsappRaw ? '+'.ltrim($whatsappRaw, '+') : '+595 982 774586';
        }

        $esFamiliar = $turno->paciente->user_id !== $notifiable->id;

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.aviso-turno', [
                'notificacion' => $this->notificacion,
                'turno' => $turno,
                'tipo' => $tipo,
                'datos' => $this->datos,
                'destinatario' => $notifiable,
                'esFamiliar' => $esFamiliar,
                'nombreDestinatario' => $notifiable->nombreVisible(),
                'nombrePaciente' => trim("{$turno->paciente->nombre} {$turno->paciente->apellido}"),
                'whatsapp' => $whatsappFormateado,
                'cuando' => $cuando,
            ]);
    }
}
