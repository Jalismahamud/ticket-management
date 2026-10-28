<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public ?User $resolvedBy = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resolverName = $this->resolvedBy?->name ?? 'Our Support Team';

        return (new MailMessage)
            ->success()
            ->subject("[RESOLVED] Ticket #{$this->ticket->ticket_number} - {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your support ticket #{$this->ticket->ticket_number} has been marked as **Resolved** by {$resolverName}.")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Priority:** " . strtoupper($this->ticket->priority))
            ->action('View Ticket Details', route('tickets.show', $this->ticket))
            ->line('If your issue is not fully resolved, you can reply directly or reopen the ticket through your support portal.')
            ->line('Thank you for contacting support!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'resolved_by' => $this->resolvedBy?->name ?? 'System',
            'type' => 'ticket_resolved',
            'message' => "Ticket #{$this->ticket->ticket_number} has been resolved.",
        ];
    }
}
