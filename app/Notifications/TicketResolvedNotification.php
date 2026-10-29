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
        public ?User $resolvedBy = null,
        public string $action = 'Resolved' // 'Resolved', 'Closed', 'Reopened'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actorName = $this->resolvedBy?->name ?? 'Our Support Team';
        $actionUpper = strtoupper($this->action);

        $mail = (new MailMessage)
            ->subject("[{$actionUpper}] Ticket #{$this->ticket->ticket_number} - {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},");

        if ($this->action === 'Closed') {
            $mail->line("Support ticket #{$this->ticket->ticket_number} has been **Closed** by {$actorName}.")
                ->line("**Subject:** {$this->ticket->subject}")
                ->action('View Ticket Details', route('tickets.show', $this->ticket))
                ->line('If you require any further assistance, please feel free to open a new support request.');
        } elseif ($this->action === 'Reopened') {
            $mail->line("Support ticket #{$this->ticket->ticket_number} has been **Reopened** by {$actorName}.")
                ->line("**Subject:** {$this->ticket->subject}")
                ->action('Open Conversation', route('tickets.show', $this->ticket))
                ->line('The ticket has returned to active review status.');
        } else {
            $mail->success()
                ->line("Your support ticket #{$this->ticket->ticket_number} has been marked as **Resolved** by {$actorName}.")
                ->line("**Subject:** {$this->ticket->subject}")
                ->line("**Priority:** " . strtoupper($this->ticket->priority))
                ->action('View Ticket Details', route('tickets.show', $this->ticket))
                ->line('If your issue is not fully resolved, you can reply directly or reopen the ticket through your support portal.')
                ->line('Thank you for contacting support!');
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $actionLower = strtolower($this->action);
        $verb = match ($actionLower) {
            'closed' => 'closed',
            'reopened' => 'reopened',
            default => 'resolved',
        };

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'resolved_by' => $this->resolvedBy?->name ?? 'System',
            'type' => 'ticket_resolved',
            'message' => "Ticket #{$this->ticket->ticket_number} has been {$verb}.",
        ];
    }
}
