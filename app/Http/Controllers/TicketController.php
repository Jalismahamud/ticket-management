<?php

namespace App\Http\Controllers;

use App\Exceptions\TicketAlreadyAssignedException;
use App\Helpers\FileHelper;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\SlaCalculatorService;
use App\Services\TicketAssignmentService;
use App\Services\TicketNumberService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function __construct(
        protected TicketAssignmentService $assignmentService
    ) {}

    /**
     * Display a listing of tickets with search, filtering, and tab scopes.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Ticket::query()
            ->with(['category', 'creator', 'assignee'])
            ->forUser($user);

        // 1. Tab / Status quick filters
        $tab = $request->get('tab', 'all');
        match ($tab) {
            'active' => $query->active(),
            'resolved' => $query->closedOrResolved(),
            'unassigned' => $query->unassigned(),
            'breached' => $query->slaBreached(),
            'approaching' => $query->slaApproaching(),
            default => null,
        };

        // 2. Keyword Search (Ticket Number, Subject, Description)
        if ($search = $request->filled('search') ? trim($request->search) : null) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // 3. Category Filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 4. Priority Filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // 5. Status Filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'resolved') {
                $query->closedOrResolved();
            } else {
                $query->where('status', $status);
            }
        }

        // 6. Assigned Agent Filter
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->unassigned();
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // 7. SLA Status Filter
        if ($request->filled('sla_status')) {
            match ($request->sla_status) {
                'breached' => $query->slaBreached(),
                'approaching' => $query->slaApproaching(),
                'within_sla' => $query->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                    ->where(function ($q) {
                        $q->whereNull('sla_breached_at')
                          ->where('sla_due_at', '>', now());
                    }),
                default => null,
            };
        }

        // Sort by latest reply or creation
        $tickets = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        // Calculate counts for quick tab badges
        $countsQuery = Ticket::forUser($user);
        $counts = [
            'all' => (clone $countsQuery)->count(),
            'active' => (clone $countsQuery)->active()->count(),
            'resolved' => (clone $countsQuery)->closedOrResolved()->count(),
            'unassigned' => (clone $countsQuery)->unassigned()->count(),
            'breached' => (clone $countsQuery)->slaBreached()->count(),
        ];

        $categories = Category::active()->get();
        $agents = User::agents()->get();

        return view('pages.tickets.index', compact('tickets', 'categories', 'agents', 'counts', 'tab'));
    }

    /**
     * Show the ticket creation form.
     */
    public function create(): View
    {
        $categories = Category::active()->get();
        $agents = User::agents()->get();

        return view('pages.tickets.create', [
            'categories' => $categories,
            'agents' => $agents,
            'title' => 'Create New Ticket - Support Portal',
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'auto_assign' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,txt,csv'],
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
            'assigned_to' => $user->canManageTickets() ? ($validated['assigned_to'] ?? null) : null,
            'sla_due_at' => $slaDueDate,
            'last_reply_at' => Carbon::now(),
        ]);

        // Audit Activity
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => TicketActivity::TYPE_CREATED,
            'description' => "Ticket created by {$user->name}",
            'properties' => [
                'priority' => $ticket->priority,
                'sla_due_at' => $slaDueDate->toDateTimeString(),
            ],
        ]);

        // Handle File Attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $upload = FileHelper::upload($file, 'attachments');
                $ticket->attachments()->create([
                    'user_id' => $user->id,
                    'file_name' => $upload['file_name'],
                    'file_path' => $upload['file_path'],
                    'file_size' => $upload['file_size'],
                    'mime_type' => $upload['mime_type'],
                ]);
            }
        }

        // Automatic assignment if requested or configured
        if (!$ticket->assigned_to && ($request->boolean('auto_assign') || $user->isCustomer())) {
            $this->assignmentService->autoAssign($ticket);
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Ticket #{$ticket->ticket_number} created successfully!");
    }

    /**
     * Display a specific ticket with conversations, attachments, and timeline.
     */
    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        /** @var User $user */
        $user = Auth::user();

        $ticket->load(['category', 'creator', 'assignee', 'attachments.user']);

        // Load replies: Customer can NEVER see internal notes
        $repliesQuery = $ticket->replies()->with(['user', 'attachments'])->oldest();
        if ($user->isCustomer()) {
            $repliesQuery->public();
        }
        $replies = $repliesQuery->get();

        $activities = $ticket->activities()->with('user')->latest()->get();
        $agents = User::agents()->get();

        return view('pages.tickets.show', [
            'ticket' => $ticket,
            'replies' => $replies,
            'activities' => $activities,
            'agents' => $agents,
            'title' => "Ticket #{$ticket->ticket_number} - {$ticket->subject}",
        ]);
    }

    /**
     * Add a reply or internal note to the ticket.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('reply', $ticket);

        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'message' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
            'new_status' => ['nullable', 'in:open,in_progress,waiting_for_customer,resolved,closed'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,txt,csv'],
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

        // Upload attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $upload = FileHelper::upload($file, 'attachments');
                $reply->attachments()->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'file_name' => $upload['file_name'],
                    'file_path' => $upload['file_path'],
                    'file_size' => $upload['file_size'],
                    'mime_type' => $upload['mime_type'],
                ]);
            }
        }

        // Update ticket lifecycle & timestamps
        $ticketUpdates = [
            'last_reply_at' => Carbon::now(),
        ];

        if ($user->isAgent() && !$ticket->first_response_at && !$isInternal) {
            $ticketUpdates['first_response_at'] = Carbon::now();
        }

        // Automatic status change or explicit transition
        if (!empty($validated['new_status']) && $user->canManageTickets()) {
            $ticketUpdates['status'] = $validated['new_status'];
            if ($validated['new_status'] === Ticket::STATUS_RESOLVED) {
                $ticketUpdates['resolved_at'] = Carbon::now();
            }
        } elseif ($user->isCustomer() && $ticket->status === Ticket::STATUS_WAITING_CUSTOMER) {
            $ticketUpdates['status'] = Ticket::STATUS_IN_PROGRESS;
        }

        $ticket->update($ticketUpdates);

        // Activity Log
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => $isInternal ? TicketActivity::TYPE_INTERNAL_NOTE : TicketActivity::TYPE_REPLIED,
            'description' => $isInternal
                ? "Internal note added by {$user->name}"
                : "Reply added by {$user->name}",
        ]);

        $successMsg = $isInternal ? 'Internal note added successfully.' : 'Reply posted successfully.';
        return back()->with('success', $successMsg);
    }

    /**
     * Agent claims an unassigned ticket concurrently.
     */
    public function claim(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('claim', $ticket);

        try {
            $this->assignmentService->claim($ticket, Auth::user());
            return back()->with('success', "You have successfully claimed ticket #{$ticket->ticket_number}!");
        } catch (TicketAlreadyAssignedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign or reassign ticket to an agent.
     */
    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);

        $request->validate([
            'agent_id' => ['required', 'exists:users,id'],
        ]);

        $agent = User::findOrFail($request->agent_id);
        $this->assignmentService->assign($ticket, $agent, Auth::user());

        return back()->with('success', "Ticket #{$ticket->ticket_number} assigned to {$agent->name}.");
    }

    /**
     * Resolve the ticket.
     */
    public function resolve(Ticket $ticket): RedirectResponse
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
            'description' => 'Ticket marked as Resolved by ' . Auth::user()->name,
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been resolved.");
    }

    /**
     * Close the ticket.
     */
    public function close(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('close', $ticket);

        $ticket->update([
            'status' => Ticket::STATUS_CLOSED,
            'closed_at' => Carbon::now(),
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'type' => TicketActivity::TYPE_STATUS_CHANGED,
            'description' => 'Ticket closed by ' . Auth::user()->name,
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been closed.");
    }

    /**
     * Reopen a resolved or closed ticket.
     */
    public function reopen(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $ticket->update([
            'status' => Ticket::STATUS_OPEN,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'type' => TicketActivity::TYPE_STATUS_CHANGED,
            'description' => 'Ticket reopened by ' . Auth::user()->name,
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been reopened.");
    }

    /**
     * Securely download ticket attachment with authorization check.
     */
    public function downloadAttachment(TicketAttachment $attachment): StreamedResponse
    {
        $ticket = $attachment->ticket;
        Gate::authorize('view', $ticket);

        // Security Check: Customer cannot download attachment from an internal note
        if (Auth::user()->isCustomer() && $attachment->reply?->is_internal) {
            abort(403, 'Unauthorized access to internal attachment.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }
}
