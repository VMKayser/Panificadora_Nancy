<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * Enlace de un solo uso para restablecer la contraseña (expira según auth.passwords.users.expire).
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $brandName = Config::get('app.name', 'Panificadora Nancy');
        $minutos = Config::get('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Restablece tu contraseña de ' . $brandName)
            ->greeting('Hola ' . ($notifiable->name ?? '') . ',')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Crear una nueva contraseña', $this->resetUrl($notifiable))
            ->line("El enlace vence en {$minutos} minutos y solo se puede usar una vez.")
            ->line('Si no pediste este cambio, ignora este correo: tu contraseña sigue siendo la misma.')
            ->salutation($brandName);
    }

    protected function resetUrl($notifiable): string
    {
        $frontend = rtrim((string) (Config::get('app.frontend_url') ?: Config::get('app.url')), '/');

        return $frontend . '/restablecer-clave?' . http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
