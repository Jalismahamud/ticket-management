@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6" x-data="csvImporter({{ isset($report) ? \Illuminate\Support\Js::from($report) : 'null' }})">
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
                    Import large sets of support tickets with real-time stream tracking, automatic SLA assignment, and duplicate prevention.
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

        <!-- Live Percentage Progress Bar (Active During Import) -->
        <div x-show="isImporting" x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            class="rounded-2xl border border-brand-200 bg-brand-50/40 p-6 shadow-theme-xs dark:border-brand-500/20 dark:bg-brand-500/5 space-y-5">

            <!-- Progress Header & Live % Display -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-theme-xs">
                        <svg class="size-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Importing Tickets in Progress...</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="progress.message"></p>
                    </div>
                </div>

                <div class="text-end">
                    <span class="text-3xl font-extrabold font-mono tracking-tight text-brand-600 dark:text-brand-400" x-text="progress.percentage + '%'"></span>
                </div>
            </div>

            <!-- Animated Progress Bar -->
            <div class="relative w-full h-4 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner">
                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 via-indigo-500 to-brand-600 transition-all duration-300 ease-out relative"
                    :style="'width: ' + progress.percentage + '%'">
                    <!-- Shimmer / Striped effect -->
                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                </div>
            </div>

            <!-- Mini Real-Time Counter Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                <div class="rounded-xl border border-gray-200/80 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 text-center">
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Total Rows</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white" x-text="progress.total || 'Calculating...'"></span>
                </div>
                <div class="rounded-xl border border-gray-200/80 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 text-center">
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Processed</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white" x-text="progress.processed || 0"></span>
                </div>
                <div class="rounded-xl border border-success-200 bg-success-50/50 p-3 dark:border-success-500/20 dark:bg-success-500/10 text-center">
                    <span class="block text-[11px] text-success-700 dark:text-success-400 font-medium">Imported</span>
                    <span class="text-lg font-bold text-success-700 dark:text-success-300" x-text="progress.imported || 0"></span>
                </div>
                <div class="rounded-xl border border-error-200 bg-error-50/50 p-3 dark:border-error-500/20 dark:bg-error-500/10 text-center">
                    <span class="block text-[11px] text-error-700 dark:text-error-400 font-medium">Skipped / Invalid</span>
                    <span class="text-lg font-bold text-error-700 dark:text-error-300" x-text="progress.skipped || 0"></span>
                </div>
            </div>
        </div>

        <!-- Import Execution Report (Shown when completed) -->
        <template x-if="report !== null">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Import Execution Summary
                    </h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                        100% Completed
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.02]">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Total Rows Processed</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="report.total"></span>
                    </div>

                    <div class="rounded-xl border border-success-200 p-4 bg-success-50/50 dark:border-success-500/20 dark:bg-success-500/10">
                        <span class="block text-xs text-success-700 dark:text-success-400 font-medium">Successfully Imported</span>
                        <span class="text-2xl font-bold text-success-700 dark:text-success-300" x-text="report.imported"></span>
                    </div>

                    <div class="rounded-xl border border-error-200 p-4 bg-error-50/50 dark:border-error-500/20 dark:bg-error-500/10">
                        <span class="block text-xs text-error-700 dark:text-error-400 font-medium">Skipped / Invalid</span>
                        <span class="text-2xl font-bold text-error-700 dark:text-error-300" x-text="report.skipped"></span>
                    </div>
                </div>

                <!-- Errors list if any -->
                <template x-if="report.errors && report.errors.length > 0">
                    <div class="mt-4 rounded-xl border border-error-200 bg-error-50/60 p-4 dark:border-error-500/30 dark:bg-error-500/10">
                        <span class="block text-sm font-semibold text-error-800 dark:text-error-300 mb-2">
                            Errors & Warnings Report (<span x-text="report.errors.length"></span>):
                        </span>
                        <ul class="max-h-48 overflow-y-auto space-y-1 text-xs text-error-700 dark:text-error-400 list-disc ps-5">
                            <template x-for="(err, idx) in report.errors" :key="idx">
                                <li x-text="err"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <div class="pt-3 flex flex-col sm:flex-row sm:justify-end gap-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="resetForm()"
                        class="w-full sm:w-auto text-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition">
                        Import Another File
                    </button>
                    <a href="{{ route('tickets.index') }}"
                        class="w-full sm:w-auto text-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        View All Tickets &rarr;
                    </a>
                </div>
            </div>
        </template>

        <!-- Upload Form Container -->
        <div x-show="!isImporting && report === null"
            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <form @submit.prevent="startImport($event)" method="POST" action="{{ route('tickets.import.post') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Upload CSV File <span class="text-error-500">*</span>
                    </label>
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-8 text-center dark:border-gray-700 dark:bg-white/[0.02]">
                        <input type="file" name="csv_file" x-ref="fileInput" @change="onFileSelected($event)" accept=".csv,text/csv,text/plain" required
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
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition cursor-pointer">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Start Batch Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alpine.js Component Script -->
    <script>
        function csvImporter(initialReport) {
            return {
                isImporting: false,
                importId: null,
                pollInterval: null,
                report: initialReport,
                progress: {
                    percentage: 0,
                    processed: 0,
                    total: 0,
                    imported: 0,
                    skipped: 0,
                    message: 'Initializing import stream...',
                },

                onFileSelected(event) {
                    this.report = null;
                },

                resetForm() {
                    this.report = null;
                    this.isImporting = false;
                    this.progress.percentage = 0;
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                },

                generateUUID() {
                    if (window.crypto && window.crypto.randomUUID) {
                        return window.crypto.randomUUID();
                    }
                    return 'imp-' + Date.now() + '-' + Math.random().toString(36).substring(2, 9);
                },

                async startImport(event) {
                    const form = event.target;
                    const fileInput = this.$refs.fileInput;

                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        alert('Please choose a CSV file first.');
                        return;
                    }

                    this.importId = this.generateUUID();
                    this.isImporting = true;
                    this.report = null;
                    this.progress.percentage = 2;
                    this.progress.message = 'Uploading CSV to server...';

                    // Start background polling every 500ms for live % updates
                    this.pollInterval = setInterval(() => {
                        this.fetchProgress();
                    }, 500);

                    const formData = new FormData(form);
                    formData.set('import_id', this.importId);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: formData
                        });

                        if (!response.ok) {
                            const errData = await response.json().catch(() => ({}));
                            throw new Error(errData.message || 'Import failed. Please verify file format.');
                        }

                        const result = await response.json();
                        this.progress.percentage = 100;
                        this.progress.message = 'Finalizing and compiling report...';

                        clearInterval(this.pollInterval);
                        setTimeout(() => {
                            this.report = result.report;
                            this.isImporting = false;
                        }, 500);

                    } catch (error) {
                        clearInterval(this.pollInterval);
                        this.isImporting = false;
                        alert(error.message || 'An unexpected error occurred during import.');
                    }
                },

                async fetchProgress() {
                    if (!this.importId || !this.isImporting) return;

                    try {
                        const res = await fetch(`/tickets/import/progress/${this.importId}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data && data.percentage !== undefined) {
                                this.progress.percentage = Math.max(this.progress.percentage, data.percentage);
                                this.progress.processed = data.processed || this.progress.processed;
                                this.progress.total = data.total || this.progress.total;
                                this.progress.imported = data.imported || this.progress.imported;
                                this.progress.skipped = data.skipped || this.progress.skipped;
                                if (data.message) {
                                    this.progress.message = data.message;
                                }

                                if (data.status === 'completed' && data.report) {
                                    clearInterval(this.pollInterval);
                                    this.progress.percentage = 100;
                                    setTimeout(() => {
                                        this.report = data.report;
                                        this.isImporting = false;
                                    }, 400);
                                }
                            }
                        }
                    } catch (e) {
                        // Soft network hiccup during poll, ignore
                    }
                }
            };
        }
    </script>
@endsection
