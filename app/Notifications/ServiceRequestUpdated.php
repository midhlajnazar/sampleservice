<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRequestUpdated extends Notification
{
    public function __construct(
        public ServiceRequest $serviceRequest,
        public ?string $comment = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $request = $this->serviceRequest;

        return (new MailMessage)
            ->subject("[{$request->reference}] {$request->service->name}: {$request->status->label()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$request->service->name} application {$request->reference} is now **{$request->status->label()}**.")
            ->when($this->comment, fn (MailMessage $mail) => $mail->line("Note: {$this->comment}"))
            ->action('View application', route('requests.show', $request));
    }
}
