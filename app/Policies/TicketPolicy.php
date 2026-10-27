<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the specific ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        // Customers can only view their own tickets
        return (int) $ticket->created_by === (int) $user->id;
    }

    /**
     * Determine whether the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the ticket details.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent()) {
            return true;
        }

        // Customer can only update while ticket is open and not terminal
        return (int) $ticket->created_by === (int) $user->id && !$ticket->isTerminal();
    }

    /**
     * Determine whether the user can assign or reassign the ticket.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isAgent());
    }

    /**
     * Determine whether the user can claim the ticket.
     */
    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isAgent()) && is_null($ticket->assigned_to);
    }

    /**
     * Determine whether the user can reply to the ticket.
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        if (!$user->is_active || $ticket->isClosed()) {
            return false;
        }

        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        return (int) $ticket->created_by === (int) $user->id;
    }

    /**
     * Determine whether the user can add or view internal notes.
     * CRITICAL: Internal notes must NEVER be visible or accessible to customers.
     */
    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isAgent());
    }

    /**
     * Determine whether the user can change status to resolved.
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isAgent());
    }

    /**
     * Determine whether the user can close the ticket.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        return (int) $ticket->created_by === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
