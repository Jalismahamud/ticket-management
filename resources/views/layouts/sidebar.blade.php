@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 px-5 start-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 ltr:border-r rtl:border-l border-gray-200 w-[90px] [.sidebar-expanded_&]:min-w-[290px]"
    x-data="{
        openSubmenus: {
            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @foreach ($menuGroup['items'] as $itemIndex => $item)
                    @if (isset($item['subItems']) && MenuHelper::isGroupOpen($item))
                        '{{ $groupIndex }}-{{ $itemIndex }}': true,
                    @endif
                @endforeach
            @endforeach
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            this.openSubmenus[key] = !this.openSubmenus[key];
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return !!this.openSubmenus[key];
        }
    }"
    :class="{
        'translate-x-0': $store.sidebar.isMobileOpen,
        'max-xl:-translate-x-full max-xl:rtl:translate-x-full': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">
    <!-- Logo Section -->
    <div class="pt-8 pb-7 flex items-center gap-2" :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : 'justify-between'">
        <a href="/">
            <div class="hidden [.sidebar-expanded_&]:block">
                <img class="dark:hidden" src="/images/logo/logo.svg" alt="SupportDesk" width="150" height="40" />
                <img class="hidden dark:block" src="/images/logo/logo-dark.svg" alt="SupportDesk" width="150" height="40" />
            </div>
            <img class="block [.sidebar-expanded_&]:hidden" src="/images/logo/logo-icon.svg" alt="SupportDesk" width="32" height="32" />
        </a>
    </div>

    <!-- Navigation Menu -->
    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <nav class="mb-6">
            <div class="flex flex-col gap-4">
                @foreach ($menuGroups as $groupIndex => $menuGroup)
                    <div>
                        <!-- Menu Group Title -->
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider flex leading-[20px] text-gray-400 dark:text-gray-500"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                            'lg:justify-center' : 'justify-start'">
                            <template
                                x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span>{{ __($menuGroup['title']) }}</span>
                            </template>
                            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 12C4.25 11.5858 4.58579 11.25 5 11.25H19C19.4142 11.25 19.75 11.5858 19.75 12C19.75 12.4142 19.4142 12.75 19 12.75H5C4.58579 12.75 4.25 12.4142 4.25 12Z" fill="currentColor"/>
                                </svg>
                            </template>
                        </h2>

                        <!-- Menu Items -->
                        <ul class="flex flex-col gap-1">
                            @foreach ($menuGroup['items'] as $itemIndex => $item)
                                @if (!empty($item['adminOnly']) && !auth()->user()?->isAdmin())
                                    @continue
                                @endif
                                @php
                                    $hasSubItems = isset($item['subItems']);
                                    $isItemActive = MenuHelper::isItemActive($item);
                                @endphp
                                <li>
                                    @if ($hasSubItems)
                                        <!-- Dropdown Menu Item -->
                                        <button @click="toggleSubmenu({{ $groupIndex }}, {{ $itemIndex }})"
                                            class="menu-item group w-full {{ $isItemActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                            :class="[
                                                (isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) || {{ $isItemActive ? 'true' : 'false' }}) ?
                                                'menu-item-active' :
                                                'menu-item-inactive',
                                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                                'xl:justify-center' :
                                                'xl:justify-start'
                                            ]">
                                            <!-- Left Icon -->
                                            <span class="{{ $isItemActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                                :class="(isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) || {{ $isItemActive ? 'true' : 'false' }}) ?
                                                'menu-item-icon-active' :
                                                'menu-item-icon-inactive'">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>

                                            <!-- Item Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ __($item['name']) }}
                                            </span>

                                            <!-- Dropdown Chevron -->
                                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="ltr:ml-auto rtl:mr-auto size-4 transition-transform duration-200"
                                                :class="{
                                                    'rotate-180 text-brand-500': isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}),
                                                    'text-gray-500 dark:text-gray-400': !isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }})
                                                }"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)"
                                            x-collapse>
                                            <ul class="mt-1 space-y-1 ltr:ml-7 rtl:mr-7">
                                                @foreach ($item['subItems'] as $subItem)
                                                    @php
                                                        $isSubActive = MenuHelper::isSubItemActive($subItem);
                                                    @endphp
                                                    <li>
                                                        <a href="{{ $subItem['path'] }}"
                                                            class="menu-dropdown-item {{ $isSubActive ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                            <span class="flex items-center gap-2">
                                                                <span class="size-1.5 rounded-full {{ $isSubActive ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                                                <span>{{ __($subItem['name']) }}</span>
                                                            </span>
                                                            @if (!empty($subItem['badge']))
                                                                <span class="ltr:ml-auto rtl:mr-auto rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $subItem['badgeClass'] ?? 'bg-brand-50 text-brand-600' }}">
                                                                    {{ $subItem['badge'] }}
                                                                </span>
                                                            @endif
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <!-- Simple Menu Item -->
                                        <a href="{{ $item['path'] }}"
                                            class="menu-item group {{ $isItemActive ? 'menu-item-active' : 'menu-item-inactive' }}"
                                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                            'xl:justify-center' :
                                            'xl:justify-start'">
                                            <!-- Left Icon -->
                                            <span class="{{ $isItemActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>

                                            <!-- Item Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ __($item['name']) }}
                                            </span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </nav>

        <!-- Sidebar Widget -->
        <div class="hidden [.sidebar-expanded_&]:block mt-auto">
            @include('layouts.sidebar-widget')
        </div>
    </div>
</aside>
