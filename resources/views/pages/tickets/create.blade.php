@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-1">
                    <svg class="size-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to all tickets
                </a>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">
                    Create New Support Ticket
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Submit your request and our support team will handle it within the guaranteed SLA window.
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

        <!-- Creation Form Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Ticket Subject <span class="text-error-500">*</span>
                    </label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required
                        placeholder="Brief summary of the issue (e.g. Cannot process credit card payment)"
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
                            <option value="">Select an appropriate category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Priority with SLA indicator -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Priority Level <span class="text-error-500">*</span>
                        </label>
                        <select id="priority" name="priority" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low (Resolution SLA: 48 Business Hours)</option>
                            <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium (Resolution SLA: 24 Business Hours)</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High (Resolution SLA: 8 Business Hours)</option>
                            <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical (Resolution SLA: 2 Business Hours)</option>
                        </select>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                            Business hours: Sun–Thu, 9:00 AM – 6:00 PM.
                        </span>
                    </div>
                </div>

                @if (auth()->user()->canManageTickets())
                    <!-- Agent Assignment (for Staff) -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Assign to Support Agent
                            </label>
                            <select id="assigned_to" name="assigned_to"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="">Auto-assign or leave unassigned</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ old('assigned_to') == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }} ({{ $agent->department ?? $agent->role }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="auto_assign" value="1" {{ old('auto_assign', true) ? 'checked' : '' }}
                                    class="size-4 rounded border-gray-300 text-brand-500 focus:ring-brand-400 dark:border-gray-700 dark:bg-gray-900">
                                <span>Automatically assign to agent with lowest workload</span>
                            </label>
                        </div>
                    </div>
                @endif

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Detailed Description <span class="text-error-500">*</span>
                    </label>
                    <textarea id="description" name="description" rows="6" required
                        placeholder="Provide detailed steps to reproduce, error codes, and any relevant context..."
                        class="w-full rounded-lg border border-gray-300 bg-transparent p-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-white/30">{{ old('description') }}</textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label for="attachments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        File Attachments (Optional)
                    </label>
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-6 text-center dark:border-gray-700 dark:bg-white/[0.02]">
                        <input type="file" id="attachments" name="attachments[]" multiple
                            class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-400" />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Supported files: PNG, JPG, GIF, PDF, DOC, DOCX, ZIP, TXT, CSV (Max: 10MB per file)
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <a href="{{ route('tickets.index') }}"
                        class="w-full sm:w-auto text-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Cancel
                    </a>
                    <button type="submit"
                        class="w-full sm:w-auto text-center rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                        Create Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
