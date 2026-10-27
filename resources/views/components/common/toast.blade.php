<div
    x-data="{
        toasts: [],
        add(toast) {
            toast.id = Date.now() + Math.random();
            this.toasts.push(toast);
            setTimeout(() => this.remove(toast.id), toast.duration || 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-init="
        @if (session('success'))
            add({ type: 'success', message: @js(session('success')) });
        @endif
        @if (session('error'))
            add({ type: 'error', message: @js(session('error')) });
        @endif
        @if (session('warning'))
            add({ type: 'warning', message: @js(session('warning')) });
        @endif
        @if (session('info'))
            add({ type: 'info', message: @js(session('info')) });
        @endif
        window.addEventListener('notify', e => add(e.detail));
    "
    class="fixed top-5 ltr:right-5 rtl:left-5 z-999999 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-theme-lg border text-sm transition-all"
            :class="{
                'bg-white border-success-200 text-gray-800 dark:bg-gray-900 dark:border-success-500/30 dark:text-white': toast.type === 'success',
                'bg-white border-error-200 text-gray-800 dark:bg-gray-900 dark:border-error-500/30 dark:text-white': toast.type === 'error',
                'bg-white border-warning-200 text-gray-800 dark:bg-gray-900 dark:border-warning-500/30 dark:text-white': toast.type === 'warning',
                'bg-white border-brand-200 text-gray-800 dark:bg-gray-900 dark:border-brand-500/30 dark:text-white': toast.type === 'info' || !toast.type
            }"
        >
            <div class="shrink-0 mt-0.5">
                <template x-if="toast.type === 'success'">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15">
                        <svg class="size-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </span>
                </template>
                <template x-if="toast.type === 'error'">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-error-50 text-error-600 dark:bg-error-500/15">
                        <svg class="size-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </span>
                </template>
                <template x-if="toast.type === 'warning'">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-warning-50 text-warning-600 dark:bg-warning-500/15">
                        <svg class="size-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </span>
                </template>
                <template x-if="toast.type === 'info' || !toast.type">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15">
                        <svg class="size-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    </span>
                </template>
            </div>
            <div class="flex-1 font-medium" x-text="toast.message"></div>
            <button @click="remove(toast.id)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
