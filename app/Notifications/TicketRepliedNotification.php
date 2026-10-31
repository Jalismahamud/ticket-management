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
        $senderName = $this->reply->user?->name ?? 'A user';
        $isInternal = (bool) $this->reply->is_internal;
        $subjectPrefix = $isInternal ? '[INTERNAL NOTE]' : "[{$this->ticket->ticket_number}]";

        return (new MailMessage)
            ->subject("{$subjectPrefix} New reply on: {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$senderName} posted a " . ($isInternal ? 'private internal note' : 'reply') . " on ticket #{$this->ticket->ticket_number}:")
            ->line('"' . \Illuminate\Support\Str::limit($this->reply->message, 150) . '"')
            ->action('View Full Conversation', route('tickets.show', $this->ticket))
            ->line('You can respond directly through the support portal.');
    }

    public function toArray(object $notifiable): array
    {
        $senderName = $this->reply->user?->name ?? 'Staff';
        $prefix = $this->reply->is_internal ? 'Internal note from ' : 'New reply from ';

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'reply_id' => $this->reply->id,
            'replied_by' => $senderName,
            'is_internal' => (bool) $this->reply->is_internal,
            'type' => 'ticket_replied',
            'message' => "{$prefix}{$senderName} on Ticket #{$this->ticket->ticket_number}.",
        ];
    }
}
