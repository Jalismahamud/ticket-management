<div class="relative" x-data="{
    isOpen: false,
    subDropdownOpen: false,
    currentLocale: '{{ app()->getLocale() }}' || localStorage.getItem('locale') || (localStorage.getItem('dir') === 'rtl' ? 'ar' : 'en'),
    languages: [
        {
            id: 'en',
            name: 'English',
            shortName: 'English',
            flag: 'flag-us.svg',
            dir: 'ltr'
        },
        {
            id: 'ar',
            name: 'Arabic (Saudi)',
            shortName: 'Arabic',
            flag: 'flag-sa.svg',
            badge: 'RTL',
            dir: 'rtl'
        },
        {
            id: 'es',
            name: 'Español',
            shortName: 'Español',
            flag: 'flag-es.svg',
            dir: 'ltr'
        },
        {
            id: 'de',
            name: 'Deutsch',
            shortName: 'Deutsch',
            flag: 'flag-de.svg',
            dir: 'ltr'
        }
    ],
    get currentLang() {
        return this.languages.find(l => l.id === this.currentLocale) || this.languages[0];
    },
    toggleDropdown() {
        this.isOpen = !this.isOpen;
        if (!this.isOpen) {
            this.subDropdownOpen = false;
        }
    },
    closeDropdown() {
        this.isOpen = false;
        this.subDropdownOpen = false;
    },
    selectLanguage(lang) {
        this.currentLocale = lang.id;
        const dir = lang.dir || (lang.id === 'ar' ? 'rtl' : 'ltr');
        localStorage.setItem('locale', lang.id);
        localStorage.setItem('dir', dir);
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', lang.id);
        window.location.href = '/locale/' + lang.id;
    }
}" @click.outside="closeDropdown()">
    <!-- User Trigger -->
    <button
        class="flex items-center text-gray-700 dark:text-gray-400"
        type="button"
        @click="toggleDropdown()"
    >
        <span class="mr-3 overflow-hidden rounded-full size-10 rtl:mr-0 rtl:ml-3 bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center border border-brand-500/20 shrink-0">
            @if(auth()->user()?->avatar && Storage::disk('public')->exists(auth()->user()->avatar))
                <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" class="size-full object-cover" />
            @else
                <span class="text-xs font-bold uppercase text-brand-600 dark:text-brand-400">
                    {{ substr(auth()->user()?->name ?? 'U', 0, 2) }}
                </span>
            @endif
        </span>

        <span class="hidden sm:block mr-1 font-medium text-theme-sm rtl:mr-0 rtl:ml-1 max-w-[120px] truncate">{{ auth()->user()?->name ?? 'User' }}</span>

        <!-- Chevron Down Icon -->
        <svg
            :class="isOpen ? 'rotate-180' : ''"
            class="transition-transform duration-200 stroke-gray-500 group-hover:stroke-gray-700 dark:stroke-gray-400 dark:group-hover:stroke-gray-200"
            width="18"
            height="18"
            viewBox="0 0 18 18"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M4.5 6.75L9 11.25L13.5 6.75"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute ltr:right-0 rtl:left-0 mt-[17px] flex w-[260px] max-w-[calc(100vw-32px)] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark z-50"
        style="display: none;"
    >
        <!-- User Info -->
        <div>
            <div class="flex items-center justify-between">
                <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">{{ auth()->user()?->name ?? 'Guest User' }}</span>
                @if(auth()->user())
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium uppercase
                        {{ auth()->user()->isAdmin() ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400' : (auth()->user()->isAgent() ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300') }}">
                        {{ auth()->user()->role }}
                    </span>
                @endif
            </div>
            <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ auth()->user()?->email ?? 'guest@example.com' }}</span>
        </div>

        <!-- Menu Items -->
        <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
            <li>
                <a
                    href="{{ route('profile') }}"
                    class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="7" r="4"/><path d="M4 20C4 16.6863 7.58172 14 12 14C16.4183 14 20 16.6863 20 20"/>
                        </svg>
                    </span>
                    Edit Profile
                </a>
            </li>
            @if(auth()->user()?->isAdmin())
                <li>
                    <a
                        href="{{ route('settings.index') }}"
                        class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    >
                        <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>
                        System Settings
                    </a>
                </li>
            @endif
            <li>
                <a
                    href="{{ route('tickets.index') }}"
                    class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 8.5C4 7.67157 4.67157 7 5.5 7H18.5C19.3284 7 20 7.67157 20 8.5V10.5C18.8954 10.5 18 11.3954 18 12.5C18 13.6046 18.8954 14.5 20 14.5V16.5C20 17.3284 19.3284 18 18.5 18H5.5C4.67157 18 4 17.3284 4 16.5V14.5C5.10457 14.5 6 13.6046 6 12.5C6 11.3954 5.10457 10.5 4 10.5V8.5Z"/><path d="M10 7V18" stroke-dasharray="2 2.5"/><path d="M14 11.5L15.5 13L17.5 10"/>
                        </svg>
                    </span>
                    Support Tickets
                </a>
            </li>

            <!-- Language / RTL Submenu Item -->
            <li class="relative" @click.outside="subDropdownOpen = false">
                <button
                    type="button"
                    @click.stop="subDropdownOpen = !subDropdownOpen"
                    class="group flex max-h-10 w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-theme-sm font-medium transition-colors"
                    :class="subDropdownOpen
                        ? 'bg-gray-100 text-gray-900 dark:bg-white/5 dark:text-white'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300'"
                >
                    <span class="flex items-center gap-3 text-theme-sm">
                        <svg class="stroke-gray-500 group-hover:stroke-gray-700 dark:stroke-gray-400 dark:group-hover:stroke-gray-300"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12.001 2.75C17.1091 2.75 21.2501 6.89178 21.2501 11.9999C21.2501 17.108 17.1091 21.2498 12.001 21.2498M12.001 2.75C6.89289 2.75 2.75195 6.89178 2.75195 11.9999C2.75195 17.108 6.8929 21.2498 12.001 21.2498M12.001 2.75C14.2097 2.75 16.0005 6.8914 16.0005 11.9993C16.0005 17.1073 14.2098 21.2498 12.001 21.2498M12.001 2.75C9.79226 2.75 8.00195 6.89141 8.00195 11.9994C8.00195 17.1073 9.79226 21.2498 12.001 21.2498M3.24561 8.99976H20.7544M3.24561 14.9998H20.7544"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <span>Language</span>
                    </span>

                    <span
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-theme-xs font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"
                    >
                        <span x-text="currentLang.shortName"></span>
                        <img :src="'/images/icons/' + currentLang.flag" :alt="currentLang.shortName" class="size-3.5 shrink-0 overflow-hidden rounded-full object-cover" />
                    </span>
                </button>

                <!-- Submenu Flyout -->
                <div x-show="subDropdownOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute top-11 ltr:-left-2 rtl:-right-2 w-[250px] rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg md:top-0 ltr:md:right-[calc(100%+14px)] ltr:md:left-auto rtl:md:left-[calc(100%+14px)] rtl:md:right-auto dark:border-gray-800 dark:bg-gray-dark z-50"
                    style="display: none;"
                >
                    <ul class="flex flex-col gap-1">
                        <template x-for="lang in languages" :key="lang.id">
                            <li>
                                <button
                                    type="button"
                                    @click="selectLanguage(lang)"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 ltr:text-left rtl:text-right text-theme-sm font-medium transition-colors"
                                    :class="currentLocale === lang.id
                                        ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-1.5 shrink-0 rounded-full transition-opacity"
                                            :class="currentLocale === lang.id ? 'bg-brand-500 opacity-100 dark:bg-brand-400' : 'opacity-0'"></span>
                                        <img :src="'/images/icons/' + lang.flag" :alt="lang.name" class="size-5 shrink-0 overflow-hidden rounded-full object-cover" />
                                        <span class="truncate" x-text="lang.name"></span>
                                    </span>

                                    <template x-if="lang.badge">
                                        <span
                                            class="rounded bg-warning-50 px-1.5 py-0.5 text-theme-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-400"
                                            x-text="lang.badge">
                                        </span>
                                    </template>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </li>
        </ul>

        <!-- Sign Out -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button
                type="submit"
                class="flex items-center w-full gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                @click="closeDropdown()"
            >
                <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                Sign out
            </button>
        </form>
    </div>
</div>
