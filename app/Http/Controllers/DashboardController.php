<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display ticket analytics and SLA dashboard.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $now = Carbon::now();
        $warningMinutes = config('sla.approaching_warning_minutes', 120);

        $baseQuery = Ticket::query()->forUser($user);

        // Optimized single query for metric aggregates
        $metrics = (clone $baseQuery)
            ->selectRaw("
                COUNT(CASE WHEN status IN ('open', 'in_progress', 'waiting_for_customer') THEN 1 END) as open_count,
                COUNT(CASE WHEN assigned_to IS NULL AND status NOT IN ('resolved', 'closed') THEN 1 END) as unassigned_count,
                COUNT(CASE WHEN priority = 'critical' AND status NOT IN ('resolved', 'closed') THEN 1 END) as critical_count,
                COUNT(CASE WHEN status IN ('resolved', 'closed') THEN 1 END) as resolved_count,
                COUNT(CASE WHEN (sla_breached_at IS NOT NULL OR (sla_due_at < ? AND status NOT IN ('resolved', 'closed'))) THEN 1 END) as breached_count,
                COUNT(CASE WHEN status NOT IN ('resolved', 'closed') AND sla_breached_at IS NULL AND sla_due_at >= ? AND sla_due_at <= ? THEN 1 END) as approaching_count
            ", [
                $now->toDateTimeString(),
                $now->toDateTimeString(),
                $now->copy()->addMinutes($warningMinutes)->toDateTimeString()
            ])
            ->first();

        // Tickets grouped by priority
        $prioritiesCount = (clone $baseQuery)
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $ticketsByPriority = [
            'critical' => $prioritiesCount['critical'] ?? 0,
            'high'     => $prioritiesCount['high'] ?? 0,
            'medium'   => $prioritiesCount['medium'] ?? 0,
            'low'      => $prioritiesCount['low'] ?? 0,
        ];

        // Active workload by agent
        $agentsWorkload = User::agents()
            ->withCount(['ticketsAssigned' => function ($q) {
                $q->whereIn('status', [
                    Ticket::STATUS_OPEN,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_CUSTOMER,
                ]);
            }])
            ->orderByDesc('tickets_assigned_count')
            ->take(6)
            ->get();

        // Recent active tickets
        $recentTickets = (clone $baseQuery)
            ->with(['category', 'creator', 'assignee'])
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('pages.dashboard.tickets', compact(
            'metrics',
            'ticketsByPriority',
            'agentsWorkload',
            'recentTickets'
        ));
    }
}
