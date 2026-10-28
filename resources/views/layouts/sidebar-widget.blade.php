@php
    $start = \App\Models\Setting::get('business_start', '09:00');
    $end = \App\Models\Setting::get('business_end', '18:00');
    $formattedStart = \Carbon\Carbon::createFromFormat('H:i', $start)->format('g:i A');
    $formattedEnd = \Carbon\Carbon::createFromFormat('H:i', $end)->format('g:i A');
    $days = (array) \App\Models\Setting::get('business_days', [0, 1, 2, 3, 4]);
    $dayNames = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
    $firstDay = $dayNames[reset($days) ?? 0] ?? 'Sun';
    $lastDay = $dayNames[end($days) ?? 4] ?? 'Thu';
@endphp

<div class="mx-auto mb-6 w-full max-w-60 rounded-2xl bg-brand-50/60 p-4 text-center dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20">
    <div class="mx-auto mb-2.5 flex size-9 items-center justify-center rounded-xl bg-brand-500 text-white shadow-theme-xs">
        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h4 class="text-xs font-semibold uppercase tracking-wider text-brand-900 dark:text-brand-300">
        Business SLA Hours
    </h4>
    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 font-medium">
        {{ $firstDay }} – {{ $lastDay }}: {{ $formattedStart }} – {{ $formattedEnd }}
    </p>
    <div class="mt-3 flex items-center justify-center gap-1.5 text-[11px] font-medium text-success-600 dark:text-success-400">
        <span class="size-1.5 rounded-full bg-success-500 animate-pulse"></span>
        SLA Engine Active
    </div>
</div>
