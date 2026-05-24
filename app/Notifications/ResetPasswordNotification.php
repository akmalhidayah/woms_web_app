<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Reset Password Akun WOMS')
            ->view([
                'html' => 'emails.auth.reset-password',
                'text' => 'emails.auth.reset-password-text',
            ], [
                'userName' => $notifiable->name,
                'resetUrl' => $url,
                'expiresIn' => $minutes,
                'logoStPath' => public_path('assets/branding/logos/logo-st.png'),
                'logoBmsPath' => public_path('assets/branding/logos/logo-bms2.png'),
                'logoStUrl' => asset('assets/branding/logos/logo-st.png'),
                'logoBmsUrl' => asset('assets/branding/logos/logo-bms2.png'),
            ]);
    }
}
