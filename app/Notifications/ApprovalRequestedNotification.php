<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $documentType,
        public readonly string $documentNumber,
        public readonly string $roleLabel,
        public readonly string $approvalUrl,
        public readonly ?Carbon $expiresAt,
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
        return (new MailMessage)
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject("Permintaan Approval {$this->documentType} - {$this->documentNumber}")
            ->view([
                'html' => 'emails.approval.requested',
                'text' => 'emails.approval.requested-text',
            ], [
                'userName' => $notifiable->name,
                'documentType' => $this->documentType,
                'documentNumber' => $this->documentNumber,
                'roleLabel' => $this->roleLabel,
                'approvalUrl' => $this->approvalUrl,
                'expiresAt' => $this->expiresAt?->format('d/m/Y H:i'),
                'logoStPath' => public_path('assets/branding/logos/logo-st.png'),
                'logoBmsPath' => public_path('assets/branding/logos/logo-bms2.png'),
                'logoStUrl' => asset('assets/branding/logos/logo-st.png'),
                'logoBmsUrl' => asset('assets/branding/logos/logo-bms2.png'),
            ]);
    }
}
