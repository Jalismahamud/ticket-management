@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Top Breadcrumb & Actions Bar -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-1">
                    <svg class="size-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Tickets List
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xl font-bold text-gray-900 dark:text-white">{{ $ticket->ticket_number }}</span>
                    
                    {{-- Priority Badge --}}
                    @php
                        $priorityClass = match($ticket->priority) {
                            'critical' => 'bg-error-50 text-error-700 border-error-200 dark:bg-error-500/15 dark:text-error-400 dark:border-error-500/30',
                            'high' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-500/15 dark:text-orange-400 dark:border-orange-500/30',
                            'medium' => 'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-500/15 dark:text-brand-400 dark:border-brand-500/30',
                            'low' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $statusClass = match($ticket->status) {
                            'open' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
                            'in_progress' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
                            'waiting_for_customer' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400',
                            'resolved' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
                            'closed' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold uppercase {{ $priorityClass }}">
                        {{ $ticket->priority }}
                    </span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $statusClass }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>

                    {{-- SLA Status --}}
                    @if ($ticket->isTerminal())
                        <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">
                            Resolved
                        </span>
                    @elseif ($ticket->isSlaBreached())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-semibold text-error-700 dark:bg-error-500/15 dark:text-error-400 animate-pulse">
                            <span class="size-1.5 rounded-full bg-error-600"></span>
                            SLA Breached ({{ $ticket->sla_due_at?->diffForHumans() }})
                        </span>
                    @elseif ($ticket->isSlaApproaching())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">
                            <span class="size-1.5 rounded-full bg-warning-600"></span>
                            Approaching SLA Breach (Due {{ $ticket->sla_due_at?->diffForHumans() }})
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            SLA Due: {{ $ticket->sla_due_at?->format('M d, H:i') }}
                        </span>
                    @endif
                </div>
                <h2 class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                    {{ $ticket->subject }}
                </h2>
            </div>

            <!-- Action Buttons Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                @can('update', $ticket)
                    <a href="{{ route('tickets.edit', $ticket) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Ticket
                    </a>
                @endcan

                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>

                @can('claim', $ticket)
                    <form method="POST" action="{{ route('tickets.claim', $ticket) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3.5 py-2 text-xs font-semibold text-white shadow-theme-xs transition hover:bg-brand-600">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Claim Ticket
                        </button>
                    </form>
                @endcan

                @can('resolve', $ticket)
                    @if (!$ticket->isResolved())
                        <form method="POST" action="{{ route('tickets.resolve', $ticket) }}">
                            @csrf
                            <button type="submit" data-confirm="Are you sure you want to mark this ticket as Resolved?"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-success-300 bg-success-50 px-3.5 py-2 text-xs font-semibold text-success-700 transition hover:bg-success-100 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Mark Resolved
                            </button>
                        </form>
                    @endif
                @endcan

                @can('close', $ticket)
                    @if (!$ticket->isClosed())
                        <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                            @csrf
                            <button type="submit" data-confirm="Are you sure you want to close this ticket?"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                Close Ticket
                            </button>
                        </form>
                    @endif
                @endcan

                @if ($ticket->isTerminal())
                    <form method="POST" action="{{ route('tickets.reopen', $ticket) }}">
                        @csrf
                        <button type="submit" data-confirm="Are you sure you want to reopen this ticket?"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-brand-300 bg-brand-50 px-3.5 py-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400">
                            Reopen Ticket
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Main Layout: Left conversation & Right metadata -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Conversation Stream (2 Columns) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Original Description Card -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex size-10 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                {{ substr($ticket->creator->name, 0, 1) }}
                            </span>
                            <div>
                                <span class="block font-semibold text-gray-900 dark:text-white">{{ $ticket->creator->name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Created {{ $ticket->created_at->format('M d, Y h:i A') }} ({{ $ticket->created_at->diffForHumans() }})</span>
                            </div>
                        </div>
                        <span class="text-xs rounded-full bg-gray-100 px-2.5 py-1 text-gray-600 dark:bg-gray-800 dark:text-gray-400">Initial Request</span>
                    </div>

                    <div class="pt-4 text-sm leading-relaxed text-gray-800 dark:text-gray-200 whitespace-pre-line">
                        {{ $ticket->description }}
                    </div>

                    <!-- Initial Attachments -->
                    @if ($ticket->attachments->whereNull('reply_id')->count() > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <span class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-2">Attached Files:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($ticket->attachments->whereNull('reply_id') as $attachment)
                                    <a href="{{ route('attachments.download', $attachment) }}"
                                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                        <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>{{ $attachment->file_name }}</span>
                                        <span class="text-[10px] text-gray-400">({{ $attachment->formatted_size }})</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Conversation Replies Stream -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Conversation History ({{ $replies->count() }})
                    </h3>

                    @forelse ($replies as $reply)
                        <div class="rounded-2xl border p-5 transition shadow-theme-xs
                            {{ $reply->is_internal
                                ? 'border-warning-300 bg-warning-50/40 dark:border-warning-500/30 dark:bg-warning-500/5'
                                : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' }}">

                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex size-8 items-center justify-center rounded-full text-xs font-semibold
                                        {{ $reply->is_internal ? 'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-300' : ($reply->user->canManageTickets() ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }}">
                                        {{ substr($reply->user->name, 0, 1) }}
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $reply->user->name }}</span>
                                            <span class="text-[11px] rounded-full px-2 py-0.2 uppercase font-medium
                                                {{ $reply->user->canManageTickets() ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                                {{ $reply->user->role }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ $reply->created_at->format('M d, Y h:i A') }} ({{ $reply->created_at->diffForHumans() }})</span>
                                    </div>
                                </div>

                                @if ($reply->is_internal)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-warning-100 px-2 py-0.5 text-xs font-semibold text-warning-800 dark:bg-warning-500/20 dark:text-warning-300">
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Internal Note (Hidden from Customer)
                                    </span>
                                @endif
                            </div>

                            <div class="pt-3 text-sm leading-relaxed text-gray-800 dark:text-gray-200 whitespace-pre-line">
                                {{ $reply->message }}
                            </div>

                            @if ($reply->attachments->count() > 0)
                                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-wrap gap-2">
                                    @foreach ($reply->attachments as $attachment)
                                        <a href="{{ route('attachments.download', $attachment) }}"
                                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                                            <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>{{ $attachment->file_name }}</span>
                                            <span class="text-[10px] text-gray-400">({{ $attachment->formatted_size }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-400 dark:border-gray-800">
                            No replies yet. Be the first to respond below.
                        </div>
                    @endforelse
                </div>

                <!-- Reply Composer -->
                @if (!$ticket->isClosed())
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900"
                        x-data="{ isInternal: false }">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                            <h3 class="font-semibold text-gray-800 dark:text-white">
                                <span x-show="!isInternal">Reply to Customer</span>
                                <span x-show="isInternal" class="text-warning-600 dark:text-warning-400">Add Internal Staff Note</span>
                            </h3>

                            @can('addInternalNote', $ticket)
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" x-model="isInternal" class="size-4 rounded border-gray-300 text-warning-500 focus:ring-warning-400">
                                    <span>Internal Agent Note (Never visible to customer)</span>
                                </label>
                            @endcan
                        </div>

                        <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="is_internal" :value="isInternal ? 1 : 0">

                            <div>
                                <textarea name="message" rows="4" required
                                    :placeholder="isInternal ? 'Type internal notes for agents and administration...' : 'Write your response to the customer...'"
                                    class="w-full rounded-lg border p-3.5 text-sm transition focus:outline-hidden"
                                    :class="isInternal
                                        ? 'border-warning-300 bg-warning-50/20 text-gray-800 dark:border-warning-500/30 dark:bg-warning-500/5 dark:text-white'
                                        : 'border-gray-300 bg-transparent text-gray-800 focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white'"></textarea>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <!-- File Upload -->
                                <div class="flex-1">
                                    <input type="file" name="attachments[]" multiple
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:file:bg-gray-800 dark:file:text-gray-300" />
                                </div>

                                @if (auth()->user()->canManageTickets())
                                    <!-- Status Transition -->
                                    <div>
                                        <select name="new_status"
                                            class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                            <option value="">Keep Current Status ({{ $ticket->status }})</option>
                                            <option value="in_progress">Set In Progress</option>
                                            <option value="waiting_for_customer">Set Waiting for Customer</option>
                                            <option value="resolved">Mark as Resolved</option>
                                        </select>
                                    </div>
                                @endif

                                <!-- Submit -->
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-medium text-white shadow-theme-xs transition"
                                    :class="isInternal ? 'bg-warning-600 hover:bg-warning-700' : 'bg-brand-500 hover:bg-brand-600'">
                                    <span x-show="!isInternal">Post Reply</span>
                                    <span x-show="isInternal">Save Note</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400">
                        This ticket is closed. Reopen the ticket to continue the conversation.
                    </div>
                @endif
            </div>

            <!-- Right: Metadata & Activity Audit Timeline (1 Column) -->
            <div class="space-y-6">
                <!-- Ticket Details Widget -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="font-semibold text-gray-800 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-800 text-sm uppercase tracking-wide">
                        Ticket Information
                    </h3>

                    <dl class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Category</dt>
                            <dd class="font-medium text-gray-800 dark:text-white">{{ $ticket->category->name }}</dd>
                        </div>

                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Created By</dt>
                            <dd class="font-medium text-gray-800 dark:text-white text-end">
                                <div>{{ $ticket->creator->name }}</div>
                                <div class="text-xs text-gray-400">{{ $ticket->creator->email }}</div>
                            </dd>
                        </div>

                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Assigned Agent</dt>
                            <dd class="font-medium text-gray-800 dark:text-white text-end">
                                @if ($ticket->assignee)
                                    <div>{{ $ticket->assignee->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $ticket->assignee->department ?? 'Support' }}</div>
                                @else
                                    <span class="text-xs italic text-gray-400">Unassigned</span>
                                @endif
                            </dd>
                        </div>

                        @can('assign', $ticket)
                            <!-- Quick Reassign Dropdown -->
                            <div class="py-2.5">
                                <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="agent_id" required
                                        class="h-8 w-full rounded-md border border-gray-300 bg-transparent px-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option value="">Reassign Agent...</option>
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                                                {{ $agent->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="h-8 rounded-md bg-gray-900 px-3 text-xs font-medium text-white hover:bg-gray-800 dark:bg-brand-500">
                                        Assign
                                    </button>
                                </form>
                            </div>
                        @endcan

                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">SLA Resolution Due</dt>
                            <dd class="font-mono text-xs font-medium text-gray-800 dark:text-white">
                                {{ $ticket->sla_due_at ? $ticket->sla_due_at->format('M d, Y H:i') : 'N/A' }}
                            </dd>
                        </div>

                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">First Response</dt>
                            <dd class="text-xs text-gray-700 dark:text-gray-300">
                                {{ $ticket->first_response_at ? $ticket->first_response_at->format('M d, H:i') : 'Pending' }}
                            </dd>
                        </div>

                        @if ($ticket->resolved_at)
                            <div class="py-2.5 flex items-center justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Resolved At</dt>
                                <dd class="text-xs text-success-600 dark:text-success-400">
                                    {{ $ticket->resolved_at->format('M d, Y H:i') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Audit History / Activity Timeline Widget -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="font-semibold text-gray-800 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-800 text-sm uppercase tracking-wide">
                        Activity & Audit Timeline
                    </h3>

                    <div class="mt-4 flow-root">
                        <ul class="-mb-4 space-y-4">
                            @forelse ($activities as $activity)
                                <li class="relative flex gap-3">
                                    <!-- Circle Indicator -->
                                    <div class="relative flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 mt-0.5">
                                        <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>

                                    <div class="flex-1 text-xs">
                                        <p class="font-medium text-gray-800 dark:text-gray-200 leading-snug">
                                            {{ $activity->description }}
                                        </p>
                                        <span class="text-gray-400 text-[11px]">
                                            {{ $activity->created_at->format('M d, H:i') }} ({{ $activity->created_at->diffForHumans() }})
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="text-xs text-gray-400">No activity recorded yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
