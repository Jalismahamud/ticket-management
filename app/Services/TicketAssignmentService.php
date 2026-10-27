<?php

namespace App\Services;

use App\Exceptions\TicketAlreadyAssignedException;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketAssignmentService
{
    /**
     * Concurrently claim an unassigned ticket by a support agent.
     * Prevents race conditions using database pessimistic locking.
     *
     * @param Ticket $ticket
     * @param User $agent
     * @return Ticket
     * @throws TicketAlreadyAssignedException
     */
    public function claim(Ticket $ticket, User $agent): Ticket
    {
        return DB::transaction(function () use ($ticket, $agent) {
            /** @var Ticket $lockedTicket */
            $lockedTicket = Ticket::where('id', $ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTicket->assigned_to !== null) {
                $assigneeName = $lockedTicket->assignee?->name ?? 'another agent';
                throw new TicketAlreadyAssignedException(
                    "Ticket #{$lockedTicket->ticket_number} has already been assigned to {$assigneeName}."
                );
            }

            $lockedTicket->assigned_to = $agent->id;
            if ($lockedTicket->status === Ticket::STATUS_OPEN) {
                $lockedTicket->status = Ticket::STATUS_IN_PROGRESS;
            }
            $lockedTicket->save();

            TicketActivity::create([
                'ticket_id' => $lockedTicket->id,
                'user_id' => $agent->id,
                'type' => TicketActivity::TYPE_ASSIGNED,
                'description' => "Ticket claimed by {$agent->name}",
                'properties' => [
                    'assigned_to' => $agent->id,
                    'agent_name' => $agent->name,
                    'method' => 'manual_claim',
                ],
            ]);

            return $lockedTicket;
        });
    }

    /**
     * Manually assign or reassign a ticket to a specific agent.
     *
     * @param Ticket $ticket
     * @param User $agent
     * @param User|null $assignedBy
     * @return Ticket
     */
    public function assign(Ticket $ticket, User $agent, ?User $assignedBy = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $agent, $assignedBy) {
            /** @var Ticket $lockedTicket */
            $lockedTicket = Ticket::where('id', $ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousAgent = $lockedTicket->assignee?->name;
            $lockedTicket->assigned_to = $agent->id;

            if ($lockedTicket->status === Ticket::STATUS_OPEN) {
                $lockedTicket->status = Ticket::STATUS_IN_PROGRESS;
            }
            $lockedTicket->save();

            $description = $assignedBy
                ? "Ticket assigned to {$agent->name} by {$assignedBy->name}"
                : "Ticket automatically assigned to {$agent->name}";

            if ($previousAgent && $previousAgent !== $agent->name) {
                $description .= " (reassigned from {$previousAgent})";
            }

            TicketActivity::create([
                'ticket_id' => $lockedTicket->id,
                'user_id' => $assignedBy?->id,
                'type' => TicketActivity::TYPE_ASSIGNED,
                'description' => $description,
                'properties' => [
                    'assigned_to' => $agent->id,
                    'agent_name' => $agent->name,
                    'assigned_by' => $assignedBy?->id,
                    'method' => $assignedBy ? 'manual_assignment' : 'auto_workload',
                ],
            ]);

            return $lockedTicket;
        });
    }

    /**
     * Automatically assign a ticket using the Agent Workload (Least Active Tickets) strategy.
     * Tie-breaker: least recently assigned ticket.
     *
     * @param Ticket $ticket
     * @return User|null
     */
    public function autoAssign(Ticket $ticket): ?User
    {
        $bestAgent = $this->findBestAgentForAssignment($ticket->category_id);

        if (!$bestAgent) {
            return null;
        }

        $this->assign($ticket, $bestAgent, null);

        return $bestAgent;
    }

    /**
     * Find the best agent for automatic assignment based on least pending active tickets.
     *
     * @param int|null $categoryId
     * @return User|null
     */
    public function findBestAgentForAssignment(?int $categoryId = null): ?User
    {
        // Query active support agents with their current active ticket count
        return User::agents()
            ->withCount(['ticketsAssigned' => function ($query) {
                $query->whereIn('status', [
                    Ticket::STATUS_OPEN,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_CUSTOMER,
                ]);
            }])
            ->orderBy('tickets_assigned_count', 'asc')
            ->orderBy('id', 'asc') // Consistent tie-breaker
            ->first();
    }
}
