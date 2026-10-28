@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Breadcrumb & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-1">
                    <svg class="size-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to all tickets
                </a>
                <h1 class="text-title-md font-semibold text-gray-800 dark:text-white/90">
                    Bulk CSV Ticket Import
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Import large sets of support tickets with stream-based processing, automatic SLA assignment, and duplicate prevention.
                </p>
            </div>
            <div>
                <a href="{{ route('tickets.import.sample') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Sample CSV
                </a>
            </div>
        </div>

        @if (isset($report))
            <!-- Import Report Cards -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">Import Execution Summary</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.02]">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Total Rows Processed</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report['total'] }}</span>
                    </div>

                    <div class="rounded-xl border border-success-200 p-4 bg-success-50/50 dark:border-success-500/20 dark:bg-success-500/10">
                        <span class="block text-xs text-success-700 dark:text-success-400 font-medium">Successfully Imported</span>
                        <span class="text-2xl font-bold text-success-700 dark:text-success-300">{{ $report['imported'] }}</span>
                    </div>

                    <div class="rounded-xl border border-error-200 p-4 bg-error-50/50 dark:border-error-500/20 dark:bg-error-500/10">
                        <span class="block text-xs text-error-700 dark:text-error-400 font-medium">Skipped / Invalid</span>
                        <span class="text-2xl font-bold text-error-700 dark:text-error-300">{{ $report['skipped'] }}</span>
                    </div>
                </div>

                @if (!empty($report['errors']))
                    <div class="mt-4 rounded-xl border border-error-200 bg-error-50/60 p-4 dark:border-error-500/30 dark:bg-error-500/10">
                        <span class="block text-sm font-semibold text-error-800 dark:text-error-300 mb-2">
                            Errors & Warnings Report ({{ count($report['errors']) }}):
                        </span>
                        <ul class="max-h-48 overflow-y-auto space-y-1 text-xs text-error-700 dark:text-error-400 list-disc ps-5">
                            @foreach ($report['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="pt-3 flex flex-col sm:flex-row sm:justify-end gap-3">
                    <a href="{{ route('tickets.index') }}"
                        class="w-full sm:w-auto text-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        View All Tickets
                    </a>
                </div>
            </div>
        @endif

        <!-- Upload Form -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <form method="POST" action="{{ route('tickets.import.post') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Upload CSV File <span class="text-error-500">*</span>
                    </label>
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-8 text-center dark:border-gray-700 dark:bg-white/[0.02]">
                        <input type="file" name="csv_file" accept=".csv,text/csv,text/plain" required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-400" />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Maximum upload size: 20MB. Make sure your file contains headers matching our sample CSV template.
                        </p>
                    </div>
                </div>

                <!-- CSV Guidelines -->
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 text-xs text-gray-600 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400 space-y-2">
                    <span class="font-semibold block text-gray-800 dark:text-gray-200">Expected Column Headers:</span>
                    <ul class="list-disc ps-5 space-y-1">
                        <li><strong class="font-mono text-gray-800 dark:text-white">subject</strong>: Title of the support request (Required)</li>
                        <li><strong class="font-mono text-gray-800 dark:text-white">description</strong>: Full problem description (Required)</li>
                        <li><strong class="font-mono text-gray-800 dark:text-white">priority</strong>: Priority level: <code class="text-brand-500">low</code>, <code class="text-brand-500">medium</code>, <code class="text-brand-500">high</code>, or <code class="text-brand-500">critical</code></li>
                        <li><strong class="font-mono text-gray-800 dark:text-white">category</strong>: Existing category name (e.g. Technical Support, Billing & Invoicing)</li>
                        <li><strong class="font-mono text-gray-800 dark:text-white">customer_email</strong>: Valid email address of customer</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Start Batch Import
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
