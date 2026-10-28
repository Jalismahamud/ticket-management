<?php

namespace App\Helpers;

class MenuHelper
{
    public static function getMainNavItems(): array
    {
        return [
            [
                'name' => 'Dashboard',
                'icon' => 'dashboard',
                'path' => '/',
                'route' => 'dashboard',
            ],
            [
                'name' => 'Tickets',
                'icon' => 'ticket',
                'subItems' => [
                    [
                        'name' => 'All Tickets',
                        'path' => '/tickets',
                        'key' => 'tickets.index',
                    ],
                    [
                        'name' => 'Create Ticket',
                        'path' => '/tickets/create',
                        'key' => 'tickets.create',
                    ],
                    [
                        'name' => 'Active Tickets',
                        'path' => '/tickets?tab=active',
                        'key' => 'tickets.active',
                    ],
                    [
                        'name' => 'Approaching SLA',
                        'path' => '/tickets?tab=approaching',
                        'key' => 'tickets.approaching',
                        'badge' => 'SLA',
                        'badgeClass' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400',
                    ],
                    [
                        'name' => 'SLA Breached',
                        'path' => '/tickets?tab=breached',
                        'key' => 'tickets.breached',
                        'badge' => 'Alert',
                        'badgeClass' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400',
                    ],
                    [
                        'name' => 'Resolved / Closed',
                        'path' => '/tickets?tab=resolved',
                        'key' => 'tickets.resolved',
                    ],
                    [
                        'name' => 'Import CSV',
                        'path' => '/tickets/import',
                        'key' => 'tickets.import',
                    ],
                ],
            ],
            [
                'name' => 'User Profile',
                'icon' => 'user-profile',
                'path' => '/profile',
                'route' => 'profile',
            ],
            [
                'name' => 'System Settings',
                'icon' => 'settings',
                'path' => '/settings',
                'route' => 'settings.index',
                'adminOnly' => true,
            ],
        ];
    }

    public static function getMenuGroups(): array
    {
        return [
            [
                'title' => 'Menu',
                'items' => self::getMainNavItems(),
            ],
        ];
    }

    public static function isItemActive(array $item): bool
    {
        if (isset($item['subItems'])) {
            foreach ($item['subItems'] as $subItem) {
                if (self::isSubItemActive($subItem)) {
                    return true;
                }
            }
            return request()->is('tickets*');
        }

        if (isset($item['route']) && request()->routeIs($item['route'])) {
            return true;
        }

        if (isset($item['path'])) {
            $path = ltrim($item['path'], '/');
            return $path === '' ? request()->is('/') : request()->is($path . '*');
        }

        return false;
    }

    public static function isSubItemActive(array $subItem): bool
    {
        $key = $subItem['key'] ?? null;
        $tab = request('tab') ?: request('status');

        return match ($key) {
            'tickets.create' => request()->routeIs('tickets.create') || request()->is('tickets/create'),
            'tickets.import' => request()->routeIs('tickets.import*') || request()->is('tickets/import*'),
            'tickets.active' => request()->is('tickets*') && $tab === 'active',
            'tickets.approaching' => request()->is('tickets*') && $tab === 'approaching',
            'tickets.breached' => request()->is('tickets*') && $tab === 'breached',
            'tickets.resolved' => request()->is('tickets*') && ($tab === 'resolved' || $tab === 'closed'),
            'tickets.index' => request()->is('tickets*')
                && !request()->is('tickets/create')
                && !request()->is('tickets/import*')
                && !request()->routeIs('tickets.create')
                && !request()->routeIs('tickets.import*')
                && empty($tab),
            default => request()->fullUrlIs(url($subItem['path'])) || request()->is(ltrim($subItem['path'], '/')),
        };
    }

    public static function isGroupOpen(array $item): bool
    {
        return self::isItemActive($item);
    }

    public static function getIconSvg(string $iconName): string
    {
        $icons = [
            'dashboard' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',

            'ticket' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5C4 7.67157 4.67157 7 5.5 7H18.5C19.3284 7 20 7.67157 20 8.5V10.5C18.8954 10.5 18 11.3954 18 12.5C18 13.6046 18.8954 14.5 20 14.5V16.5C20 17.3284 19.3284 18 18.5 18H5.5C4.67157 18 4 17.3284 4 16.5V14.5C5.10457 14.5 6 13.6046 6 12.5C6 11.3954 5.10457 10.5 4 10.5V8.5Z"/><path d="M10 7V18" stroke-dasharray="2 2.5"/><path d="M14 11.5L15.5 13L17.5 10"/></svg>',

            'user-profile' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M4 20C4 16.6863 7.58172 14 12 14C16.4183 14 20 16.6863 20 20"/></svg>',

            'settings' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
        ];

        return $icons[$iconName] ?? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/></svg>';
    }
}
