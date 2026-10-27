<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
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
            ->subject("[{$this->ticket->ticket_number}] You have been assigned to a ticket")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned to support ticket #{$this->ticket->ticket_number}.")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Priority:** " . strtoupper($this->ticket->priority))
            ->line("**SLA Due Date:** " . ($this->ticket->sla_due_at ? $this->ticket->sla_due_at->toDayDateTimeString() : 'N/A'))
            ->action('Open Ticket', route('tickets.show', $this->ticket))
            ->line('Please review and provide a first response within the SLA window.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'type' => 'ticket_assigned',
            'message' => "You were assigned to Ticket #{$this->ticket->ticket_number}.",
        ];
    }
}
