<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Services\SlaCalculatorService;
use App\Services\TicketAssignmentService;
use App\Services\TicketNumberService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TicketApiController extends Controller
{
    public function __construct(
        protected TicketAssignmentService $assignmentService
    ) {}

    /**
     * List tickets for authenticated user with search and status filtering.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Ticket::query()
            ->with(['category', 'creator', 'assignee'])
            ->withCount('replies')
            ->forUser($user);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%");
            });
        }

        $tickets = $query->latest()->paginate($request->integer('per_page', 15));

        return TicketResource::collection($tickets);
    }

    /**
     * Show single ticket details.
     */
    public function show(Ticket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['category', 'creator', 'assignee', 'replies.user']);

        return response()->json([
            'success' => true,
            'data' => new TicketResource($ticket),
            'replies' => $ticket->replies
                ->filter(fn ($r) => !Auth::user()->isCustomer() || !$r->is_internal)
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'message' => $r->message,
                    'is_internal' => (bool) $r->is_internal,
                    'author' => $r->user?->name,
                    'created_at' => $r->created_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    /**
     * Create a ticket via API.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'auto_assign' => ['nullable', 'boolean'],
        ]);

        $ticketNumber = TicketNumberService::generate();
        $slaDueDate = SlaCalculatorService::calculateDeadline($validated['priority']);

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'priority' => $validated['priority'],
            'status' => Ticket::STATUS_OPEN,
            'created_by' => $user->id,
            'sla_due_at' => $slaDueDate,
            'last_reply_at' => Carbon::now(),
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => TicketActivity::TYPE_CREATED,
            'description' => "Ticket created via REST API by {$user->name}",
            'properties' => ['priority' => $ticket->priority, 'sla_due_at' => $slaDueDate->toDateTimeString()],
        ]);

        if ($request->boolean('auto_assign') || $user->isCustomer()) {
            $this->assignmentService->autoAssign($ticket);
        }

        $ticket->load(['category', 'creator', 'assignee']);

        return response()->json([
            'success' => true,
            'message' => "Ticket #{$ticket->ticket_number} created successfully.",
            'data' => new TicketResource($ticket),
        ], 201);
    }

    /**
     * Add reply or internal note via API.
     */
    public function reply(Request $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('reply', $ticket);

        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'message' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $isInternal = false;
        if ($request->boolean('is_internal')) {
            Gate::authorize('addInternalNote', $ticket);
            $isInternal = true;
        }

        $reply = $ticket->replies()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
            'is_internal' => $isInternal,
        ]);

        $ticketUpdates = ['last_reply_at' => Carbon::now()];
        if ($user->isCustomer() && $ticket->status === Ticket::STATUS_WAITING_CUSTOMER) {
            $ticketUpdates['status'] = Ticket::STATUS_IN_PROGRESS;
        }
        $ticket->update($ticketUpdates);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => $isInternal ? TicketActivity::TYPE_INTERNAL_NOTE : TicketActivity::TYPE_REPLIED,
            'description' => ($isInternal ? 'Internal note' : 'Reply') . " posted via API by {$user->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => $isInternal ? 'Internal note added.' : 'Reply posted.',
            'reply' => [
                'id' => $reply->id,
                'message' => $reply->message,
                'is_internal' => (bool) $reply->is_internal,
                'created_at' => $reply->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Resolve a ticket via API.
     */
    public function resolve(Ticket $ticket): JsonResponse
    {
        Gate::authorize('resolve', $ticket);

        $ticket->update([
            'status' => Ticket::STATUS_RESOLVED,
            'resolved_at' => Carbon::now(),
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'type' => TicketActivity::TYPE_STATUS_CHANGED,
            'description' => 'Ticket marked as Resolved via API by ' . Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Ticket #{$ticket->ticket_number} has been resolved.",
            'data' => new TicketResource($ticket->fresh(['category', 'creator', 'assignee'])),
        ]);
    }
}
