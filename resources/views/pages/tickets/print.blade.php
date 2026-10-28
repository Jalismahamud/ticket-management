<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket-{{ $ticket->ticket_number }}-Report</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        @media print {
            body {
                background-color: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break-avoid {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .print-shadow-none {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="py-8 px-4 sm:px-6">

    <!-- Top Action Bar (Screen Only - Hidden in Print) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('tickets.show', $ticket) }}"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Ticket #{{ $ticket->ticket_number }}
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-600 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Official Report Document Container -->
    <main class="max-w-4xl mx-auto bg-white border border-gray-200 rounded-xl p-8 sm:p-10 shadow-sm print:border-0 print:p-0 print-shadow-none">

        <!-- Header -->
        <header class="border-b-2 border-gray-900 pb-5 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black tracking-tight text-gray-900 uppercase">
                            {{ config('app.name', 'SupportDesk') }}
                        </span>
                        <span class="rounded bg-brand-50 px-2 py-0.5 text-[11px] font-bold uppercase text-brand-700 border border-brand-200">
                            SLA Management
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 uppercase tracking-widest font-semibold mt-1">
                        Official Support Incident & Resolution Report
                    </p>
                </div>

                <div class="text-end text-xs text-gray-600 space-y-1">
                    <div class="text-base font-bold font-mono text-gray-900">#{{ $ticket->ticket_number }}</div>
                    <div><span class="font-medium text-gray-500">Date Printed:</span> {{ now()->format('M d, Y - h:i A') }}</div>
                    <div><span class="font-medium text-gray-500">Printed By:</span> {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</div>
                </div>
            </div>
        </header>

        <!-- Executive Metadata Summary Table -->
        <section class="border border-gray-300 rounded-lg p-5 mb-6 bg-gray-50/70 page-break-avoid">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Ticket Number</span>
                    <span class="font-mono font-bold text-gray-900 text-sm">{{ $ticket->ticket_number }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Status</span>
                    @php
                        $statusBadgeColor = match($ticket->status) {
                            'open' => 'bg-blue-100 text-blue-800 border-blue-300',
                            'in_progress' => 'bg-amber-100 text-amber-800 border-amber-300',
                            'waiting_for_customer' => 'bg-purple-100 text-purple-800 border-purple-300',
                            'resolved' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            'closed' => 'bg-gray-100 text-gray-700 border-gray-300',
                            default => 'bg-gray-100 text-gray-700 border-gray-300',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase border {{ $statusBadgeColor }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>
                </div>
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Priority / SLA Target</span>
                    @php
                        $priorityBadgeColor = match($ticket->priority) {
                            'critical' => 'bg-rose-100 text-rose-800 border-rose-300',
                            'high' => 'bg-orange-100 text-orange-800 border-orange-300',
                            'medium' => 'bg-blue-100 text-blue-800 border-blue-300',
                            'low' => 'bg-gray-100 text-gray-700 border-gray-300',
                            default => 'bg-gray-100 text-gray-700 border-gray-300',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase border {{ $priorityBadgeColor }}">
                        {{ $ticket->priority }} ({{ config("sla.priorities.{$ticket->priority}", 24) }}h SLA)
                    </span>
                </div>
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Category</span>
                    <span class="font-bold text-gray-900">{{ $ticket->category->name }}</span>
                </div>

                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Created By (Customer)</span>
                    <span class="font-semibold text-gray-900">{{ $ticket->creator->name }}</span>
                    <div class="text-[11px] text-gray-500">{{ $ticket->creator->email }}</div>
                </div>
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Assigned Engineer</span>
                    <span class="font-semibold text-gray-900">{{ $ticket->assignee?->name ?? 'Unassigned' }}</span>
                    <div class="text-[11px] text-gray-500">{{ $ticket->assignee?->department ?? 'Support Department' }}</div>
                </div>
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Created Date & Time</span>
                    <span class="font-medium text-gray-900">{{ $ticket->created_at->format('M d, Y - h:i A') }}</span>
                </div>
                <div>
                    <span class="block text-gray-500 font-semibold uppercase text-[10px] tracking-wider">SLA Resolution Due</span>
                    <span class="font-mono font-semibold text-gray-900">{{ $ticket->sla_due_at?->format('M d, Y H:i') ?? 'N/A' }}</span>
                    @if ($ticket->isTerminal())
                        <div class="text-emerald-700 font-bold text-[10px] mt-0.5">RESOLVED ({{ $ticket->resolved_at?->format('M d, H:i') }})</div>
                    @elseif ($ticket->isSlaBreached())
                        <div class="text-rose-700 font-bold text-[10px] mt-0.5">SLA BREACHED</div>
                    @else
                        <div class="text-emerald-700 font-bold text-[10px] mt-0.5">WITHIN SLA</div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Ticket Subject & Initial Request -->
        <section class="border border-gray-300 rounded-lg p-5 mb-6 page-break-avoid">
            <div class="border-b border-gray-200 pb-3 mb-3 flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-wider text-gray-500 block">Ticket Subject</span>
                    <h2 class="text-base font-bold text-gray-900">{{ $ticket->subject }}</h2>
                </div>
                <span class="text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded border border-gray-200">
                    Initial Requester Submission
                </span>
            </div>

            <div class="text-xs leading-relaxed text-gray-800 whitespace-pre-line">
                {{ $ticket->description }}
            </div>

            @if ($ticket->attachments->whereNull('reply_id')->count() > 0)
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block mb-1.5">Attached Documentation:</span>
                    <ul class="text-xs space-y-1">
                        @foreach ($ticket->attachments->whereNull('reply_id') as $att)
                            <li class="flex items-center gap-1.5 text-gray-700">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="font-medium">{{ $att->file_name }}</span>
                                <span class="text-gray-400 text-[10px]">({{ $att->formatted_size }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <!-- Conversation History -->
        <section class="mb-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-3 border-b border-gray-200 pb-1.5">
                Ticket Communication History ({{ $replies->count() }} Interactions)
            </h3>

            @forelse ($replies as $reply)
                <article class="border rounded-lg p-4 mb-3 page-break-avoid
                    {{ $reply->is_internal ? 'border-amber-300 bg-amber-50/60' : 'border-gray-200 bg-white' }}">

                    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900">{{ $reply->user->name }}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold uppercase border
                                {{ $reply->user->canManageTickets() ? 'bg-brand-50 text-brand-700 border-brand-200' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                                {{ $reply->user->role }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($reply->is_internal)
                                <span class="bg-amber-200 text-amber-900 border border-amber-300 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide">
                                    Internal Staff Note (Confidential)
                                </span>
                            @endif
                            <span class="text-[11px] text-gray-500 font-mono">{{ $reply->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>

                    <div class="text-xs leading-relaxed text-gray-800 whitespace-pre-line">
                        {{ $reply->message }}
                    </div>

                    @if ($reply->attachments->count() > 0)
                        <div class="mt-2.5 pt-2 border-t border-gray-200 text-[11px] text-gray-600 flex items-center gap-2">
                            <span class="font-semibold text-gray-500">Attachment:</span>
                            @foreach ($reply->attachments as $att)
                                <span class="font-medium text-gray-800">{{ $att->file_name }}</span>
                                <span class="text-gray-400 text-[10px]">({{ $att->formatted_size }})</span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="border border-dashed border-gray-300 rounded-lg p-4 text-center text-xs text-gray-500">
                    No replies or notes recorded for this ticket.
                </div>
            @endforelse
        </section>

        <!-- Audit & History Timeline -->
        <section class="border border-gray-200 rounded-lg p-4 mb-8 page-break-avoid">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-2.5 pb-1 border-b border-gray-200">
                Audit Trail & Status Transitions
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                @foreach ($activities as $act)
                    <div class="flex items-start gap-2 text-gray-700 py-1 border-b border-gray-100">
                        <span class="font-mono text-gray-400 shrink-0 text-[10px]">{{ $act->created_at->format('m/d H:i') }}</span>
                        <span class="font-medium text-gray-800">{{ $act->description }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Official Sign-off & Closing Section -->
        <section class="pt-6 border-t-2 border-gray-300 page-break-avoid">
            <div class="grid grid-cols-3 gap-6 text-center text-xs text-gray-700">
                <div>
                    <div class="border-b border-gray-400 pb-12 mb-2"></div>
                    <p class="font-bold text-gray-900">Customer Sign-off</p>
                    <p class="text-[10px] text-gray-500">{{ $ticket->creator->name }}</p>
                </div>
                <div>
                    <div class="border-b border-gray-400 pb-12 mb-2"></div>
                    <p class="font-bold text-gray-900">Support Engineer</p>
                    <p class="text-[10px] text-gray-500">{{ $ticket->assignee?->name ?? 'Assigned Engineer' }}</p>
                </div>
                <div>
                    <div class="border-b border-gray-400 pb-12 mb-2"></div>
                    <p class="font-bold text-gray-900">Supervisor Verification</p>
                    <p class="text-[10px] text-gray-500">Date: ____________________</p>
                </div>
            </div>

            <footer class="mt-8 text-center text-[10px] text-gray-400">
                Official document generated by {{ config('app.name', 'SupportDesk') }} SLA & Ticket Management System • Confidential & Proprietary
            </footer>
        </section>

    </main>

    <!-- Auto Print Script -->
    <script>
        window.addEventListener('load', function() {
            // Optional slight delay so all fonts and CSS load before print preview opens
            setTimeout(function() {
                window.print();
            }, 350);
        });
    </script>
</body>
</html>

