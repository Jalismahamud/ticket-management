<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Handle global search form submit (Enter key).
     */
    public function handle(Request $request): RedirectResponse
    {
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            return redirect()->route('tickets.index');
        }

        /** @var User $user */
        $user = Auth::user();

        // Check if query exactly matches a ticket number (case-insensitive)
        $exactTicket = Ticket::query()
            ->forUser($user)
            ->where(function ($q) use ($query) {
                $q->where('ticket_number', $query)
                  ->orWhere('ticket_number', 'LIKE', "%{$query}%");
            })
            ->first();

        // If user typed a ticket number (starts with TKT- or exact match), jump directly to that ticket
        if ($exactTicket && (str_starts_with(strtoupper($query), 'TKT-') || $exactTicket->ticket_number === strtoupper($query))) {
            return redirect()->route('tickets.show', $exactTicket);
        }

        // Otherwise redirect to tickets list with keyword search parameter
        return redirect()->route('tickets.index', ['search' => $query]);
    }

    /**
     * Live search API for global header search dropdown.
     */
    public function live(Request $request): JsonResponse
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'tickets' => [],
                'users' => [],
                'total' => 0,
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // 1. Search Tickets (by ticket_number, subject, description, phone numbers/notes in description, customer name/email)
        $tickets = Ticket::query()
            ->forUser($user)
            ->with(['creator:id,name,email', 'category:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('ticket_number', 'LIKE', "%{$query}%")
                  ->orWhere('subject', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhereHas('creator', function ($sub) use ($query) {
                      $sub->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('email', 'LIKE', "%{$query}%");
                  });
            })
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'category' => $ticket->category?->name,
                    'creator' => $ticket->creator?->name,
                    'creator_email' => $ticket->creator?->email,
                    'url' => route('tickets.show', $ticket),
                ];
            });

        // 2. Search Users / Contacts (Staff members can search customers by name, email, department)
        $users = [];
        if ($user->canManageTickets()) {
            $users = User::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%")
                      ->orWhere('department', 'LIKE', "%{$query}%");
                })
                ->withCount('ticketsCreated')
                ->take(4)
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => ucfirst($u->role),
                        'department' => $u->department,
                        'tickets_count' => $u->tickets_created_count,
                        'url' => route('tickets.index', ['customer_id' => $u->id]),
                    ];
                });
        }

        return response()->json([
            'tickets' => $tickets,
            'users' => $users,
            'total' => count($tickets) + count($users),
        ]);
    }
}

