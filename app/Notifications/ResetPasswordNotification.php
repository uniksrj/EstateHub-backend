<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $resetUrl = $frontendUrl . '/auth/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset your EstateHub password')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('We received a request to reset the password for your EstateHub account.')
            ->line('Use the button below to choose a new password and regain access to your account.')
            ->action('Reset password', $resetUrl)
            ->line('This secure link will expire in 60 minutes.')
            ->line('If you did not request a password reset, you can safely ignore this email. Your account remains protected.')
            ->salutation('EstateHub Support');
    }
}
