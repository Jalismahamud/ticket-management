<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketReply $reply
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->ticket_number}] New reply on: {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->reply->user->name} posted a new reply on ticket #{$this->ticket->ticket_number}:")
            ->line('"' . \Illuminate\Support\Str::limit($this->reply->message, 150) . '"')
            ->action('View Full Conversation', route('tickets.show', $this->ticket))
            ->line('You can respond directly through the support portal.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'reply_id' => $this->reply->id,
            'replied_by' => $this->reply->user->name,
            'type' => 'ticket_replied',
            'message' => "New reply from {$this->reply->user->name} on Ticket #{$this->ticket->ticket_number}.",
        ];
    }
}
