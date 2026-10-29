<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitacionProfesional extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return (new MailMessage)
            ->subject('Tu cuenta en Glow Dental Protect')
            ->greeting('¡Hola!')
            ->line('Te dimos de alta como profesional en el panel de Glow Dental Protect. Creá tu contraseña para ingresar y gestionar tu agenda.')
            ->action('Crear contraseña', $url)
            ->line('El enlace vence en 7 días. Si no esperabas este correo, podés ignorarlo.');
    }
}
