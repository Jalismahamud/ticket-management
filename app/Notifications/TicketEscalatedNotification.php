<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $reason = 'SLA Breached'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("[ESCALATION ALERT] Ticket #{$this->ticket->ticket_number} - {$this->reason}")
            ->greeting("Attention {$notifiable->name},")
            ->line("Ticket #{$this->ticket->ticket_number} has been automatically escalated.")
            ->line("**Reason:** {$this->reason}")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Priority:** " . strtoupper($this->ticket->priority))
            ->line("**SLA Deadline:** " . ($this->ticket->sla_due_at ? $this->ticket->sla_due_at->toDayDateTimeString() : 'N/A'))
            ->action('Take Immediate Action', route('tickets.show', $this->ticket))
            ->line('Immediate supervisory intervention or expedited resolution is recommended.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'reason' => $this->reason,
            'type' => 'ticket_escalated',
            'message' => "Ticket #{$this->ticket->ticket_number} escalated: {$this->reason}",
        ];
    }
}
