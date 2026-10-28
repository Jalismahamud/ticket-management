@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-1">
                    <svg class="size-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to ticket #{{ $ticket->ticket_number }}
                </a>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">
                    Edit Ticket #{{ $ticket->ticket_number }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Update ticket details, subject, description, category or priority classification.
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                <div class="font-semibold mb-1">Please correct the following errors:</div>
                <ul class="list-disc ps-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Edit Form Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Ticket Subject <span class="text-error-500">*</span>
                    </label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject', $ticket->subject) }}" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-white/30">
                </div>

                <!-- Category & Priority Grid -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Category <span class="text-error-500">*</span>
                        </label>
                        <select id="category_id" name="category_id" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $ticket->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (auth()->user()->canManageTickets())
                        <!-- Priority (Staff can change priority, which recalculates SLA deadline) -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Priority Level <span class="text-error-500">*</span>
                            </label>
                            <select id="priority" name="priority" required
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="low" {{ old('priority', $ticket->priority) === 'low' ? 'selected' : '' }}>
                                    Low (48 Business Hours)
                                </option>
                                <option value="medium" {{ old('priority', $ticket->priority) === 'medium' ? 'selected' : '' }}>
                                    Medium (24 Business Hours)
                                </option>
                                <option value="high" {{ old('priority', $ticket->priority) === 'high' ? 'selected' : '' }}>
                                    High (8 Business Hours)
                                </option>
                                <option value="critical" {{ old('priority', $ticket->priority) === 'critical' ? 'selected' : '' }}>
                                    Critical (2 Business Hours)
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Note: Changing priority dynamically recalculates the SLA deadline based on business hours.
                            </p>
                        </div>
                    @else
                        <!-- Customer view of priority (read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Priority Level
                            </label>
                            <div class="h-11 flex items-center px-4 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                <span class="capitalize font-semibold">{{ $ticket->priority }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                @if (auth()->user()->canManageTickets())
                    <!-- Assignee (Staff only) -->
                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Assigned Support Agent
                        </label>
                        <select id="assigned_to" name="assigned_to"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">-- Unassigned --</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" {{ old('assigned_to', $ticket->assigned_to) == $agent->id ? 'selected' : '' }}>
                                    {{ $agent->name }} ({{ $agent->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Detailed Description <span class="text-error-500">*</span>
                    </label>
                    <textarea id="description" name="description" rows="6" required
                        class="w-full rounded-lg border border-gray-300 bg-transparent p-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-white/30">{{ old('description', $ticket->description) }}</textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <a href="{{ route('tickets.show', $ticket) }}"
                        class="w-full sm:w-auto text-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                        Cancel
                    </a>
                    <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Update Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
