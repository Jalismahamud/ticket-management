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
        $channels = ['database'];
        $email = $notifiable->email ?? '';

        // Only dispatch real SMTP mail if recipient is not an RFC 2606 documentation/test domain
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/@(example\.(com|org|net)|test|localhost|invalid)$/i', $email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isAssignee = $this->ticket->assigned_to === $notifiable->id;
        $agentName = $this->ticket->assignee?->name ?? 'A support agent';

        if ($isAssignee) {
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

        return (new MailMessage)
            ->subject("[{$this->ticket->ticket_number}] Ticket Assigned to {$agentName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your support ticket #{$this->ticket->ticket_number} has been assigned to {$agentName}.")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Status:** " . ucfirst(str_replace('_', ' ', $this->ticket->status)))
            ->action('View Ticket Details', route('tickets.show', $this->ticket))
            ->line("{$agentName} is reviewing your issue and will get back to you shortly.");
    }

    public function toArray(object $notifiable): array
    {
        $isAssignee = $this->ticket->assigned_to === $notifiable->id;
        $agentName = $this->ticket->assignee?->name ?? 'an agent';

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'type' => 'ticket_assigned',
            'message' => $isAssignee
                ? "You were assigned to Ticket #{$this->ticket->ticket_number}."
                : "Ticket #{$this->ticket->ticket_number} assigned to {$agentName}.",
        ];
    }
}
