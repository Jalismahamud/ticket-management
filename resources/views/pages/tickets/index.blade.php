@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Page Breadcrumb & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">
                    Support Tickets
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Manage, track, assign and resolve support tickets within SLA deadlines.
                </p>
            </div>
            <div>
                <a href="{{ route('tickets.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Support Ticket
                </a>
            </div>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2">
            <a href="{{ route('tickets.index', ['tab' => 'all']) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition
                {{ $tab === 'all' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                <span>All Tickets</span>
                <span class="rounded-full px-2 py-0.5 text-xs bg-gray-200/80 text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $counts['all'] }}</span>
            </a>

            <a href="{{ route('tickets.index', ['tab' => 'active']) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition
                {{ $tab === 'active' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                <span>Active / In Progress</span>
                <span class="rounded-full px-2 py-0.5 text-xs bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ $counts['active'] }}</span>
            </a>

            <a href="{{ route('tickets.index', ['tab' => 'unassigned']) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition
                {{ $tab === 'unassigned' ? 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                <span>Unassigned</span>
                <span class="rounded-full px-2 py-0.5 text-xs bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-300">{{ $counts['unassigned'] }}</span>
            </a>

            <a href="{{ route('tickets.index', ['tab' => 'breached']) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition
                {{ $tab === 'breached' ? 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                <span>SLA Breached</span>
                <span class="rounded-full px-2 py-0.5 text-xs bg-error-100 text-error-800 dark:bg-error-500/20 dark:text-error-300">{{ $counts['breached'] }}</span>
            </a>

            <a href="{{ route('tickets.index', ['tab' => 'resolved']) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition
                {{ $tab === 'resolved' ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}">
                <span>Resolved / Closed</span>
                <span class="rounded-full px-2 py-0.5 text-xs bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-300">{{ $counts['resolved'] }}</span>
            </a>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6 items-center">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <!-- Keyword Search -->
                <div class="lg:col-span-2">
                    <div class="relative">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ID, subject or description..."
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent ps-9 pe-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-white/30">
                    </div>
                </div>

                <!-- Category -->
                <div>
                    <select name="category_id"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority -->
                <div>
                    <select name="priority"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All Priorities</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low (48h)</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium (24h)</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High (8h)</option>
                        <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Critical (2h)</option>
                    </select>
                </div>

                <!-- SLA Status -->
                <div>
                    <select name="sla_status"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All SLA Statuses</option>
                        <option value="within_sla" {{ request('sla_status') === 'within_sla' ? 'selected' : '' }}>Within SLA</option>
                        <option value="approaching" {{ request('sla_status') === 'approaching' ? 'selected' : '' }}>Approaching Breach</option>
                        <option value="breached" {{ request('sla_status') === 'breached' ? 'selected' : '' }}>SLA Breached</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="h-10 flex-1 rounded-lg bg-gray-900 px-3 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-brand-500 dark:hover:bg-brand-600">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'category_id', 'priority', 'status', 'assigned_to', 'sla_status']))
                        <a href="{{ route('tickets.index', ['tab' => $tab]) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5"
                            title="Clear Filters">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tickets Table Card -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead class="border-b border-gray-200 bg-gray-50/50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3.5 text-start">Ticket ID</th>
                            <th class="px-5 py-3.5 text-start">Subject & Category</th>
                            <th class="px-5 py-3.5 text-start">Customer</th>
                            <th class="px-5 py-3.5 text-start">Priority</th>
                            <th class="px-5 py-3.5 text-start">Status</th>
                            <th class="px-5 py-3.5 text-start">Assigned Agent</th>
                            <th class="px-5 py-3.5 text-start">SLA Resolution</th>
                            <th class="px-5 py-3.5 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        @forelse ($tickets as $ticket)
                            <tr class="transition hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <!-- Ticket ID -->
                                <td class="px-5 py-4 font-mono font-medium text-gray-900 dark:text-white">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="text-brand-500 hover:underline">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>

                                <!-- Subject & Category -->
                                <td class="px-5 py-4 max-w-xs">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="block font-medium text-gray-800 hover:text-brand-500 dark:text-white/90 truncate">
                                        {{ $ticket->subject }}
                                    </a>
                                    <span class="mt-0.5 inline-block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $ticket->category->name }}
                                    </span>
                                </td>

                                <!-- Customer -->
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    <div class="font-medium">{{ $ticket->creator->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $ticket->creator->email }}</div>
                                </td>

                                <!-- Priority Badge -->
                                <td class="px-5 py-4">
                                    @php
                                        $priorityClass = match($ticket->priority) {
                                            'critical' => 'bg-error-50 text-error-700 border-error-200 dark:bg-error-500/15 dark:text-error-400 dark:border-error-500/30',
                                            'high' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-500/15 dark:text-orange-400 dark:border-orange-500/30',
                                            'medium' => 'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-500/15 dark:text-brand-400 dark:border-brand-500/30',
                                            'low' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold uppercase {{ $priorityClass }}">
                                        {{ $ticket->priority }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-5 py-4">
                                    @php
                                        $statusClass = match($ticket->status) {
                                            'open' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
                                            'in_progress' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
                                            'waiting_for_customer' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400',
                                            'resolved' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
                                            'closed' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                        $statusLabel = str_replace('_', ' ', $ticket->status);
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                <!-- Assigned Agent -->
                                <td class="px-5 py-4">
                                    @if ($ticket->assignee)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex size-6 items-center justify-center rounded-full bg-brand-100 text-xs font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                                {{ substr($ticket->assignee->name, 0, 1) }}
                                            </span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $ticket->assignee->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs italic text-gray-400">Unassigned</span>
                                        @can('claim', $ticket)
                                            <form method="POST" action="{{ route('tickets.claim', $ticket) }}" class="inline ms-2">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium text-brand-500 hover:underline">Claim</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>

                                <!-- SLA Resolution Badge -->
                                <td class="px-5 py-4">
                                    @if ($ticket->isTerminal())
                                        <span class="inline-flex items-center gap-1 text-xs text-success-600 dark:text-success-400">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Completed
                                        </span>
                                    @elseif ($ticket->isSlaBreached())
                                        <div class="inline-flex flex-col">
                                            <span class="inline-flex items-center gap-1 rounded-md bg-error-50 px-2 py-0.5 text-xs font-semibold text-error-700 dark:bg-error-500/15 dark:text-error-400">
                                                <span class="size-1.5 rounded-full bg-error-500 animate-pulse"></span>
                                                SLA Breached
                                            </span>
                                            <span class="text-[11px] text-gray-400 mt-0.5">Due: {{ $ticket->sla_due_at?->diffForHumans() }}</span>
                                        </div>
                                    @elseif ($ticket->isSlaApproaching())
                                        <div class="inline-flex flex-col">
                                            <span class="inline-flex items-center gap-1 rounded-md bg-warning-50 px-2 py-0.5 text-xs font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">
                                                <span class="size-1.5 rounded-full bg-warning-500"></span>
                                                Approaching Breach
                                            </span>
                                            <span class="text-[11px] text-warning-600 dark:text-warning-400 mt-0.5">{{ $ticket->sla_due_at?->diffForHumans() }}</span>
                                        </div>
                                    @else
                                        <div class="inline-flex flex-col">
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Within SLA</span>
                                            <span class="text-[11px] text-gray-400">Due: {{ $ticket->sla_due_at?->format('M d, H:i') }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-5 py-4 text-end">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400">
                                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No tickets found</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Try adjusting your filters or search keywords.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4">
                <x-common.pagination :paginator="$tickets" />
            </div>
        </div>
    </div>
@endsection
