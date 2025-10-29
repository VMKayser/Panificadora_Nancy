<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification (in Spanish).
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifica tu correo electrónico - Panificadora Nancy')
            ->greeting('Hola ' . ($notifiable->name ?? ''))
            ->line('Gracias por registrarte en Panificadora Nancy. Antes de empezar, necesitamos verificar tu dirección de correo electrónico.')
            ->action('Verificar correo', $verificationUrl)
            ->line('Si no solicitaste esta cuenta, puedes ignorar este correo.')
            ->salutation('Saludos,\nPanificadora Nancy');
    }

    /**
     * Create the verification URL.
     */
    protected function verificationUrl($notifiable)
    {
        $expiration = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60));

        return URL::temporarySignedRoute(
            'verification.verify',
            $expiration,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
