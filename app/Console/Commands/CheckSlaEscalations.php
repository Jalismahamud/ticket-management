<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Notifications\TicketEscalatedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckSlaEscalations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically identify, escalate and notify on tickets approaching or exceeding SLA deadlines';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting SLA check and escalation process...');

        $now = Carbon::now();
        $breachedEscalatedCount = 0;
        $approachingCount = 0;

        // 1. Escalate Breached Tickets
        $breachedTickets = Ticket::query()
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', $now)
            ->where('is_escalated', false)
            ->get();

        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();

        foreach ($breachedTickets as $ticket) {
            $ticket->update([
                'is_escalated' => true,
                'escalated_at' => $now,
                'sla_breached_at' => $ticket->sla_breached_at ?: $now,
            ]);

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => null, // System action
                'type' => TicketActivity::TYPE_ESCALATED,
                'description' => "Ticket automatically escalated: SLA deadline of {$ticket->sla_due_at->format('M d, H:i')} was breached.",
                'properties' => [
                    'sla_due_at' => $ticket->sla_due_at->toDateTimeString(),
                    'breached_at' => $now->toDateTimeString(),
                ],
            ]);

            // Notify Assigned Agent and Administrators
            $recipients = collect($admins);
            if ($ticket->assignee && !$recipients->contains('id', $ticket->assignee->id)) {
                $recipients->push($ticket->assignee);
            }

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new TicketEscalatedNotification($ticket, 'SLA Resolution Deadline Breached'));
            }

            $breachedEscalatedCount++;
        }

        // 2. Identify and Alert Approaching Tickets
        $warningMinutes = config('sla.approaching_warning_minutes', 120);
        $approachingTickets = Ticket::slaApproaching($warningMinutes)->get();

        foreach ($approachingTickets as $ticket) {
            $approachingCount++;
            if ($ticket->assignee) {
                $ticket->assignee->notify(new TicketEscalatedNotification(
                    $ticket,
                    "Approaching SLA Breach in " . $now->diffInMinutes($ticket->sla_due_at) . " minutes"
                ));
            }
        }

        $this->info("Completed SLA check: {$breachedEscalatedCount} tickets escalated for breach, {$approachingCount} tickets approaching deadline.");

        return Command::SUCCESS;
    }
}
