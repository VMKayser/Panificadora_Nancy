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
        $brandName = Config::get('app.name', 'Panificadora Nancy');

        return (new MailMessage)
            ->subject('Confirma tu correo y disfruta de ' . $brandName)
            ->view('emails.verify-email', [
                'userName' => $notifiable->name ?? 'cliente',
                'brandName' => $brandName,
                'verificationUrl' => $verificationUrl,
                'frontendUrl' => $this->frontendBaseUrl(),
                'supportEmail' => Config::get('mail.from.address'),
                'supportPhone' => Config::get('support.phone', env('SUPPORT_PHONE')),
                'expirationMinutes' => Config::get('auth.verification.expire', 60),
            ]);
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

    protected function frontendBaseUrl(): string
    {
        $frontend = Config::get('app.frontend_url');

        if (empty($frontend)) {
            $frontend = Config::get('app.url', env('APP_URL', ''));
        }

        return rtrim($frontend, '/');
    }
}
