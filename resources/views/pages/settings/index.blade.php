@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-common.page-breadcrumb pageTitle="System & SLA Settings" />

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
            @csrf

            <!-- Section 1: General & Ticket Settings -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
                <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            General & Assignment Configuration
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Manage application branding, support contact, and automatic ticket assignment strategies.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        Admin Only
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="app_name" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Application Name <span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        @error('app_name')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="support_email" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Support Contact Email <span class="text-error-500">*</span>
                        </label>
                        <input type="email" id="support_email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        @error('support_email')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="ticket_prefix" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Ticket Number Prefix <span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="ticket_prefix" name="ticket_prefix" value="{{ old('ticket_prefix', $settings['ticket_prefix']) }}" required
                            placeholder="e.g. TKT"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <span class="mt-1 block text-[11px] text-gray-400">Preview: <code class="font-mono text-brand-600 dark:text-brand-400">{{ $settings['ticket_prefix'] }}-{{ date('Y') }}-000001</code></span>
                        @error('ticket_prefix')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="assignment_strategy" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Automatic Assignment Strategy <span class="text-error-500">*</span>
                        </label>
                        <select id="assignment_strategy" name="assignment_strategy" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="round_robin" {{ old('assignment_strategy', $settings['assignment_strategy']) === 'round_robin' ? 'selected' : '' }}>
                                Round-Robin (Equally rotates new tickets across active agents)
                            </option>
                            <option value="workload" {{ old('assignment_strategy', $settings['assignment_strategy']) === 'workload' ? 'selected' : '' }}>
                                Agent Workload (Assigns to the agent with the lowest open ticket count)
                            </option>
                            <option value="manual" {{ old('assignment_strategy', $settings['assignment_strategy']) === 'manual' ? 'selected' : '' }}>
                                Manual Assignment Only (Tickets stay unassigned until claimed)
                            </option>
                        </select>
                        @error('assignment_strategy')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: SLA Resolution Times -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
                <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        SLA Resolution Deadlines by Priority
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Define maximum allowed resolution hours for each ticket priority level.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-error-100 bg-error-50/20 p-4 dark:border-error-500/20 dark:bg-error-500/5">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold uppercase text-error-700 dark:text-error-400 mb-2">
                            Critical Priority
                        </span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="sla_critical" value="{{ old('sla_critical', $settings['sla_critical']) }}" min="1" max="168" required
                                class="h-10 w-24 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <span class="text-xs text-gray-500">Hours</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-orange-100 bg-orange-50/20 p-4 dark:border-orange-500/20 dark:bg-orange-500/5">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold uppercase text-orange-700 dark:text-orange-400 mb-2">
                            High Priority
                        </span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="sla_high" value="{{ old('sla_high', $settings['sla_high']) }}" min="1" max="168" required
                                class="h-10 w-24 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <span class="text-xs text-gray-500">Hours</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-brand-100 bg-brand-50/20 p-4 dark:border-brand-500/20 dark:bg-brand-500/5">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold uppercase text-brand-700 dark:text-brand-400 mb-2">
                            Medium Priority
                        </span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="sla_medium" value="{{ old('sla_medium', $settings['sla_medium']) }}" min="1" max="168" required
                                class="h-10 w-24 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <span class="text-xs text-gray-500">Hours</span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50/40 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 mb-2">
                            Low Priority
                        </span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="sla_low" value="{{ old('sla_low', $settings['sla_low']) }}" min="1" max="168" required
                                class="h-10 w-24 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <span class="text-xs text-gray-500">Hours</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 max-w-sm">
                    <label for="sla_warning_minutes" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Approaching SLA Breach Warning Threshold (Minutes) <span class="text-error-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="sla_warning_minutes" name="sla_warning_minutes" value="{{ old('sla_warning_minutes', $settings['sla_warning_minutes']) }}" min="5" max="1440" required
                            class="h-11 w-32 rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <span class="text-xs text-gray-500">Minutes before due time</span>
                    </div>
                    @error('sla_warning_minutes')
                        <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Section 3: Business Hours & Calendar -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
                <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Business Hours & Working Days
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        SLA timers pause outside of these active working days and business hours.
                    </p>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Active Business Days <span class="text-error-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @php
                                $allDays = [
                                    0 => 'Sunday',
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                ];
                                $activeDays = (array) old('business_days', $settings['business_days']);
                            @endphp
                            @foreach ($allDays as $dayIndex => $dayName)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-gray-50/50 px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-white/[0.02] dark:text-gray-300">
                                    <input type="checkbox" name="business_days[]" value="{{ $dayIndex }}"
                                        {{ in_array($dayIndex, $activeDays) ? 'checked' : '' }}
                                        class="size-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                    <span>{{ $dayName }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('business_days')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 max-w-lg">
                        <div>
                            <label for="business_start" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                Business Start Time (24h) <span class="text-error-500">*</span>
                            </label>
                            <input type="time" id="business_start" name="business_start" value="{{ old('business_start', $settings['business_start']) }}" required
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                            @error('business_start')
                                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="business_end" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                Business End Time (24h) <span class="text-error-500">*</span>
                            </label>
                            <input type="time" id="business_end" name="business_end" value="{{ old('business_end', $settings['business_end']) }}" required
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                            @error('business_end')
                                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-2">
                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto text-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Cancel
                </a>
                <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600">
                    Save System Settings
                </button>
            </div>
        </form>
    </div>
@endsection
