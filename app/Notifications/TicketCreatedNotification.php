<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->ticket_number}] Ticket Created: {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new support ticket has been submitted.")
            ->line("**Ticket Number:** {$this->ticket->ticket_number}")
            ->line("**Priority:** " . strtoupper($this->ticket->priority))
            ->line("**SLA Resolution Due:** " . ($this->ticket->sla_due_at ? $this->ticket->sla_due_at->toDayDateTimeString() : 'N/A'))
            ->action('View Ticket Details', route('tickets.show', $this->ticket))
            ->line('Our support team has been notified and will assist you shortly.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'type' => 'ticket_created',
            'message' => "Ticket #{$this->ticket->ticket_number} was created.",
        ];
    }
}
