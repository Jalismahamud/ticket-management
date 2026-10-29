@php
    $currentUser = auth()->user();
    $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
    $unreadCount = ($currentUser && $hasNotificationsTable) ? $currentUser->unreadNotifications()->count() : 0;
    $rawNotifications = ($currentUser && $hasNotificationsTable) ? $currentUser->notifications()->take(20)->get() : collect();

    $initialNotifications = $rawNotifications->map(function ($n) {
        $data = $n->data;
        $ticketId = $data['ticket_id'] ?? null;
        return [
            'id' => $n->id,
            'type' => $data['type'] ?? 'general',
            'message' => $data['message'] ?? 'Notification alert',
            'subject' => $data['subject'] ?? null,
            'ticket_id' => $ticketId,
            'ticket_number' => $data['ticket_number'] ?? null,
            'url' => $ticketId ? route('tickets.show', $ticketId) : '#',
            'is_unread' => is_null($n->read_at),
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at_human' => $n->created_at?->diffForHumans() ?? 'Just now',
        ];
    })->values()->all();
@endphp

<div class="relative" x-data="{
    dropdownOpen: false,
    activeTab: 'all',
    unreadCount: {{ $unreadCount }},
    notifications: {{ \Illuminate\Support\Js::from($initialNotifications) }},
    isLoading: false,
    pollTimer: null,

    init() {
        // Background live polling every 25s for real-time notification sync
        this.pollTimer = setInterval(() => {
            if (!document.hidden) {
                this.fetchNotifications(true);
            }
        }, 25000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.fetchNotifications(true);
            }
        });
    },

    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
        if (this.dropdownOpen) {
            this.fetchNotifications(false);
        }
    },

    closeDropdown() {
        this.dropdownOpen = false;
    },

    async fetchNotifications(silent = false) {
        if (!silent) this.isLoading = true;
        try {
            const res = await fetch(`{{ route('notifications.fetch') }}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (res.ok) {
                const data = await res.json();
                this.unreadCount = data.unread_count;
                this.notifications = data.notifications;
            }
        } catch (e) {
            console.error('Notification sync error:', e);
        } finally {
            if (!silent) this.isLoading = false;
        }
    },

    async handleNotificationClick(item, event) {
        if (item.is_unread) {
            item.is_unread = false;
            if (this.unreadCount > 0) this.unreadCount--;

            try {
                fetch(`/notifications/${item.id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } catch (e) {
                console.error('Mark read error:', e);
            }
        }
    },

    async markAllAsRead() {
        if (this.unreadCount === 0) return;

        this.unreadCount = 0;
        this.notifications.forEach(n => n.is_unread = false);

        try {
            await fetch(`{{ route('notifications.mark-all-read') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } catch (e) {
            console.error('Mark all read error:', e);
        }
    },

    async deleteNotification(item, event) {
        event.stopPropagation();
        event.preventDefault();

        const wasUnread = item.is_unread;
        this.notifications = this.notifications.filter(n => n.id !== item.id);
        if (wasUnread && this.unreadCount > 0) this.unreadCount--;

        try {
            await fetch(`/notifications/${item.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } catch (e) {
            console.error('Delete error:', e);
        }
    },

    async clearReadNotifications() {
        this.notifications = this.notifications.filter(n => n.is_unread);
        try {
            await fetch(`{{ route('notifications.clear-read') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } catch (e) {
            console.error('Clear read error:', e);
        }
    },

    get filteredNotifications() {
        if (this.activeTab === 'unread') {
            return this.notifications.filter(n => n.is_unread);
        }
        return this.notifications;
    }
}" @click.outside="closeDropdown()" @keydown.escape.window="closeDropdown()">

    <!-- Notification Bell Button -->
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-gray-900 size-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white cursor-pointer"
        @click="toggleDropdown()"
        type="button"
        title="Notifications"
        aria-label="View Notifications"
    >
        <!-- Unread Ping Indicator Badge -->
        <template x-if="unreadCount > 0">
            <span class="absolute -top-0.5 -right-0.5 z-10 flex size-5 items-center justify-center rounded-full bg-error-500 text-[10px] font-bold text-white shadow-theme-xs">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-error-400 opacity-75"></span>
                <span class="relative z-10" x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
            </span>
        </template>

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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="transform opacity-0 scale-95 translate-y-1"
        class="absolute ltr:right-0 rtl:left-0 mt-3 flex max-h-[520px] w-[calc(100vw-32px)] max-w-[390px] sm:w-[410px] flex-col rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900 z-99999"
    >
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <h5 class="text-sm font-bold text-gray-900 dark:text-white">Notifications</h5>
                <template x-if="unreadCount > 0">
                    <span class="inline-flex items-center justify-center rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
                        x-text="unreadCount + ' new'">
                    </span>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <!-- Mark All Read Action -->
                <button
                    type="button"
                    x-show="unreadCount > 0"
                    @click="markAllAsRead()"
                    class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:underline cursor-pointer">
                    Mark all read
                </button>
                <!-- Refresh Button -->
                <button
                    type="button"
                    @click="fetchNotifications(false)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                    title="Refresh Notifications">
                    <svg class="size-3.5" :class="{ 'animate-spin text-brand-500': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        <!-- Filter Tabs: All vs Unread -->
        <div class="flex items-center gap-2 pt-2.5 pb-2">
            <button
                type="button"
                @click="activeTab = 'all'"
                class="flex-1 rounded-lg py-1.5 text-xs font-medium transition cursor-pointer text-center"
                :class="activeTab === 'all'
                    ? 'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                All (<span x-text="notifications.length"></span>)
            </button>
            <button
                type="button"
                @click="activeTab = 'unread'"
                class="flex-1 rounded-lg py-1.5 text-xs font-medium transition cursor-pointer text-center"
                :class="activeTab === 'unread'
                    ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                Unread (<span x-text="unreadCount"></span>)
            </button>
        </div>

        <!-- Notification List -->
        <div class="overflow-y-auto max-h-[340px] divide-y divide-gray-100 dark:divide-gray-800/80 custom-scrollbar pr-0.5">
            <!-- Empty State -->
            <template x-if="filteredNotifications.length === 0">
                <div class="py-10 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <p class="mt-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                        <span x-show="activeTab === 'unread'">No unread notifications</span>
                        <span x-show="activeTab === 'all'">No notifications found</span>
                    </p>
                    <p class="text-[11px] text-gray-400 mt-0.5">You're all caught up with your support activities.</p>
                </div>
            </template>

            <!-- Notification Items -->
            <template x-for="item in filteredNotifications" :key="item.id">
                <div class="group relative flex items-start gap-3 p-2.5 rounded-xl transition hover:bg-gray-50 dark:hover:bg-white/[0.03]"
                    :class="item.is_unread ? 'bg-brand-50/50 dark:bg-brand-500/10' : ''">

                    <!-- Type Specific Icon -->
                    <div class="shrink-0 mt-0.5">
                        <!-- Escalated / Alert -->
                        <template x-if="item.type === 'ticket_escalated'">
                            <span class="flex size-8 items-center justify-center rounded-full bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400 border border-error-200 dark:border-error-500/30">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </span>
                        </template>

                        <!-- Assigned -->
                        <template x-if="item.type === 'ticket_assigned'">
                            <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 border border-brand-200 dark:border-brand-500/30">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                        </template>

                        <!-- Reply -->
                        <template x-if="item.type === 'ticket_replied'">
                            <span class="flex size-8 items-center justify-center rounded-full bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </span>
                        </template>

                        <!-- Resolved -->
                        <template x-if="item.type === 'ticket_resolved'">
                            <span class="flex size-8 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400 border border-success-200 dark:border-success-500/30">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                        </template>

                        <!-- Created / Other Default -->
                        <template x-if="item.type !== 'ticket_escalated' && item.type !== 'ticket_assigned' && item.type !== 'ticket_replied' && item.type !== 'ticket_resolved'">
                            <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 border border-brand-200 dark:border-brand-500/30">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </span>
                        </template>
                    </div>

                    <!-- Content & Links -->
                    <a :href="item.url"
                        @click="handleNotificationClick(item, $event)"
                        class="flex-1 min-w-0 pr-6 block focus:outline-hidden">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold text-gray-800 dark:text-white/90 line-clamp-1" x-text="item.message"></span>
                            <span x-show="item.is_unread" class="size-1.5 rounded-full bg-brand-500 shrink-0"></span>
                        </div>
                        <template x-if="item.subject">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5" x-text="item.subject"></p>
                        </template>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block font-normal" x-text="item.created_at_human"></span>
                    </a>

                    <!-- Hover Actions: Delete / Dismiss -->
                    <div class="absolute top-2.5 end-2 flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            type="button"
                            @click="deleteNotification(item, $event)"
                            class="size-6 inline-flex items-center justify-center rounded-md text-gray-400 hover:text-error-600 hover:bg-error-50 dark:hover:bg-error-500/10 transition cursor-pointer"
                            title="Delete notification">
                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer Actions -->
        <div class="pt-3 mt-1 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
            <button
                type="button"
                @click="clearReadNotifications()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition cursor-pointer">
                Clear read
            </button>
            <a href="{{ route('tickets.index') }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">
                View All Tickets &rarr;
            </a>
        </div>
    </div>
</div>
