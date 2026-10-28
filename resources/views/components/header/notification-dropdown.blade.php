@php
    $currentUser = auth()->user();
    $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
    $unreadCount = ($currentUser && $hasNotificationsTable) ? $currentUser->unreadNotifications()->count() : 0;
    $notifications = ($currentUser && $hasNotificationsTable) ? $currentUser->notifications()->take(8)->get() : collect();
@endphp

<div class="relative" x-data="{
    dropdownOpen: false,
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    }
}" @click.away="closeDropdown()">
    <!-- Notification Button -->
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-gray-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
        title="Notifications"
    >
        @if ($unreadCount > 0)
            <!-- Unread Ping Indicator -->
            <span class="absolute right-0 top-0.5 z-1 h-2.5 w-2.5 rounded-full bg-error-500">
                <span class="absolute inline-flex w-full h-full bg-error-400 rounded-full opacity-75 -z-1 animate-ping"></span>
            </span>
        @endif

        <!-- Bell Icon -->
        <svg class="size-5 fill-current" viewBox="0 0 20 20">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z" />
        </svg>
    </button>

    <!-- Dropdown Panel -->
    <div
        x-show="dropdownOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute ltr:-right-12 ltr:sm:right-0 rtl:-left-12 rtl:sm:left-0 mt-3 flex max-h-[460px] w-[calc(100vw-32px)] max-w-[360px] sm:w-[380px] flex-col rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 z-999"
    >
        <!-- Dropdown Header -->
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">Notifications</h5>
                @if ($unreadCount > 0)
                    <span class="inline-flex items-center justify-center rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        {{ $unreadCount }} new
                    </span>
                @endif
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-brand-500 hover:underline">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <!-- Notification List -->
        <ul class="flex flex-col overflow-y-auto divide-y divide-gray-100 custom-scrollbar dark:divide-gray-800">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'general';
                    $isUnread = is_null($notification->read_at);
                    $ticketId = $data['ticket_id'] ?? null;
                    $url = $ticketId ? route('tickets.show', $ticketId) : '#';
                @endphp
                <li>
                    <a href="{{ $url }}"
                        class="flex items-start gap-3 p-2.5 rounded-xl transition hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $isUnread ? 'bg-brand-50/40 dark:bg-brand-500/5' : '' }}">
                        <div class="shrink-0 mt-0.5">
                            @if ($type === 'ticket_escalated')
                                <span class="flex size-8 items-center justify-center rounded-full bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </span>
                            @elseif ($type === 'ticket_assigned')
                                <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </span>
                            @elseif ($type === 'ticket_replied')
                                <span class="flex size-8 items-center justify-center rounded-full bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                </span>
                            @else
                                <span class="flex size-8 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 dark:text-white/90 truncate">
                                {{ $data['message'] ?? 'New notification' }}
                            </p>
                            @if (!empty($data['subject']))
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                    {{ $data['subject'] }}
                                </p>
                            @endif
                            <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if ($isUnread)
                            <span class="size-2 rounded-full bg-brand-500 shrink-0 mt-2"></span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="py-8 text-center">
                    <div class="mx-auto flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <p class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">No notifications yet</p>
                </li>
            @endforelse
        </ul>
    </div>
</div>
