<?php

namespace App\Support;

use App\Models\AdminRoleMenuAccess;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminMenuRegistry
{
    public const MENU_DASHBOARD = 'dashboard';

    public const MENU_INVENTORY = 'inventory';

    public const MENU_ORDERS = 'orders';

    public const MENU_ORDER_JASA = 'order_jasa';

    public const MENU_ORDER_BENGKEL = 'order_bengkel';

    public const MENU_CREATE_HPP = 'create_hpp';

    public const MENU_VERIFIKASI_ANGGARAN = 'verifikasi_anggaran';

    public const MENU_PURCHASE_ORDER = 'purchase_order';

    public const MENU_LHPP_BAST = 'lhpp_bast';

    public const MENU_LPJ_PPL = 'lpj_ppl';

    public const MENU_GARANSI = 'garansi';

    public const MENU_DISPLAY_PEKERJAAN_BENGKEL = 'display_pekerjaan_bengkel';

    public const MENU_ACCESS_CONTROL = 'access_control';

    public const MENU_KUOTA_ANGGARAN_OA = 'kuota_anggaran_oa';

    public const MENU_USER_PANEL = 'user_panel';

    public const MENU_UPLOAD_INFORMASI = 'upload_informasi';

    public const MENU_STRUKTUR_ORGANISASI = 'struktur_organisasi';

    public const MENU_KONTRAK_JASA_FABRIKASI_KONSTRUKSI = 'kontrak_jasa_fabrikasi_konstruksi';

    /**
     * Get all admin menu definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            self::MENU_DASHBOARD => [
                'key' => self::MENU_DASHBOARD,
                'label' => 'Dashboard',
                'icon' => 'pie-chart',
                'group' => 'dashboard',
                'route_name' => 'admin.dashboard',
                'active_patterns' => ['admin.dashboard'],
                'always_visible' => true,
                'configurable' => false,
            ],
            self::MENU_INVENTORY => [
                'key' => self::MENU_INVENTORY,
                'label' => 'Inventory',
                'icon' => 'warehouse',
                'group' => 'inventory',
                'route_name' => 'admin.inventory.dashboard',
                'active_patterns' => ['admin.inventory.*'],
                'badge_count' => 0,
                'children' => [
                    ['key' => self::MENU_INVENTORY, 'label' => 'Dashboard Gudang', 'route_name' => 'admin.inventory.dashboard', 'active_patterns' => ['admin.inventory.dashboard'], 'badge_count' => 0],
                    ['key' => self::MENU_INVENTORY, 'label' => 'Master Barang', 'route_name' => 'admin.inventory.items.index', 'active_patterns' => ['admin.inventory.items.*'], 'badge_count' => 0],
                    ['key' => self::MENU_INVENTORY, 'label' => 'Stok Masuk', 'route_name' => 'admin.inventory.stock-in.index', 'active_patterns' => ['admin.inventory.stock-in.*'], 'badge_count' => 0],
                    ['key' => self::MENU_INVENTORY, 'label' => 'Koreksi Stok', 'route_name' => 'admin.inventory.adjustments.index', 'active_patterns' => ['admin.inventory.adjustments.*'], 'badge_count' => 0],
                    ['key' => self::MENU_INVENTORY, 'label' => 'Riwayat Transaksi', 'route_name' => 'admin.inventory.transactions.index', 'active_patterns' => ['admin.inventory.transactions.*'], 'badge_count' => 0],
                    ['key' => self::MENU_INVENTORY, 'label' => 'User Aplikasi', 'route_name' => 'admin.inventory.users.index', 'active_patterns' => ['admin.inventory.users.*'], 'badge_count' => 0],
                    ['key' => self::MENU_INVENTORY, 'label' => 'Master Data', 'route_name' => 'admin.inventory.master-data.index', 'active_patterns' => ['admin.inventory.master-data.*'], 'badge_count' => 0],
                ],
            ],
            self::MENU_ORDERS => [
                'key' => self::MENU_ORDERS,
                'label' => 'Order',
                'badge_key' => 'orders_total',
                'icon' => 'inbox',
                'group' => 'main',
                'route_name' => 'admin.orders.index',
                'active_patterns' => ['admin.orders.*'],
                'configurable' => false,
                'access_control_hidden' => true,
                'children' => [
                    [
                        'key' => self::MENU_ORDER_JASA,
                        'label' => 'Order Pekerjaan Jasa',
                        'badge_key' => 'order_jasa_incomplete',
                        'route_name' => 'admin.orders.index',
                        'active_patterns' => ['admin.orders.index', 'admin.orders.show', 'admin.orders.edit', 'admin.orders.create', 'admin.orders.scope-of-work.*'],
                    ],
                    [
                        'key' => self::MENU_ORDER_BENGKEL,
                        'label' => 'Order Pekerjaan Bengkel',
                        'route_name' => 'admin.orders.workshop.index',
                        'active_patterns' => ['admin.orders.workshop.*'],
                    ],
                ],
            ],
            self::MENU_ORDER_JASA => [
                'key' => self::MENU_ORDER_JASA,
                'label' => 'Order Pekerjaan Jasa',
                'icon' => 'briefcase-business',
                'group' => 'main',
                'route_name' => 'admin.orders.index',
                'active_patterns' => ['admin.orders.index', 'admin.orders.show', 'admin.orders.edit', 'admin.orders.create', 'admin.orders.scope-of-work.*'],
                'sidebar_hidden' => true,
            ],
            self::MENU_ORDER_BENGKEL => [
                'key' => self::MENU_ORDER_BENGKEL,
                'label' => 'Order Pekerjaan Bengkel',
                'icon' => 'factory',
                'group' => 'main',
                'route_name' => 'admin.orders.workshop.index',
                'active_patterns' => ['admin.orders.workshop.*'],
                'sidebar_hidden' => true,
            ],
            self::MENU_CREATE_HPP => [
                'key' => self::MENU_CREATE_HPP,
                'label' => 'Create HPP',
                'badge_key' => 'create_hpp',
                'icon' => 'pencil',
                'group' => 'main',
                'route_name' => 'admin.hpp.index',
                'active_patterns' => ['admin.hpp.*'],
            ],
            self::MENU_VERIFIKASI_ANGGARAN => [
                'key' => self::MENU_VERIFIKASI_ANGGARAN,
                'label' => 'Verifikasi Anggaran',
                'badge_key' => 'verifikasi_anggaran',
                'icon' => 'wallet',
                'group' => 'main',
                'route_name' => 'admin.budget-verification.index',
                'active_patterns' => ['admin.budget-verification.*'],
            ],
            self::MENU_PURCHASE_ORDER => [
                'key' => self::MENU_PURCHASE_ORDER,
                'label' => 'Purchase Order',
                'badge_key' => 'purchase_order',
                'icon' => 'list-checks',
                'group' => 'main',
                'route_name' => 'admin.purchase-order.index',
                'active_patterns' => ['admin.purchase-order.*'],
            ],
            self::MENU_LHPP_BAST => [
                'key' => self::MENU_LHPP_BAST,
                'label' => 'BAST',
                'badge_key' => 'bast_total',
                'icon' => 'file-text',
                'group' => 'main',
                'route_name' => 'admin.lhpp.index',
                'active_patterns' => ['admin.lhpp.*'],
                'children' => [
                    [
                        'key' => self::MENU_GARANSI,
                        'label' => 'Set Garansi',
                        'badge_key' => 'set_garansi',
                        'route_name' => 'admin.garansi.index',
                        'active_patterns' => ['admin.garansi.*'],
                    ],
                    [
                        'key' => self::MENU_LHPP_BAST,
                        'label' => 'Cek BAST',
                        'badge_key' => 'cek_bast',
                        'route_name' => 'admin.lhpp.index',
                        'active_patterns' => ['admin.lhpp.*'],
                    ],
                ],
            ],
            self::MENU_LPJ_PPL => [
                'key' => self::MENU_LPJ_PPL,
                'label' => 'LPJ / PPL',
                'badge_key' => 'lpj_ppl',
                'icon' => 'folder-open',
                'group' => 'main',
                'route_name' => 'admin.lpj.index',
                'active_patterns' => ['admin.lpj.*'],
            ],
            self::MENU_GARANSI => [
                'key' => self::MENU_GARANSI,
                'label' => 'Garansi',
                'icon' => 'shield-check',
                'group' => 'main',
                'route_name' => 'admin.garansi.index',
                'active_patterns' => ['admin.garansi.*'],
                'sidebar_hidden' => true,
            ],
            self::MENU_DISPLAY_PEKERJAAN_BENGKEL => [
                'key' => self::MENU_DISPLAY_PEKERJAAN_BENGKEL,
                'label' => 'Display Pekerjaan Bengkel',
                'icon' => 'monitor',
                'group' => 'support',
                'route_name' => 'admin.bengkel-tasks.index',
                'active_patterns' => ['admin.bengkel-tasks.*', 'admin.bengkel-pics.*'],
            ],
            self::MENU_ACCESS_CONTROL => [
                'key' => self::MENU_ACCESS_CONTROL,
                'label' => 'Access Control',
                'group' => 'other',
                'route_name' => 'admin.access-control.index',
                'active_patterns' => ['admin.access-control.*'],
                'configurable' => false,
                'super_admin_only' => true,
            ],
            self::MENU_KUOTA_ANGGARAN_OA => [
                'key' => self::MENU_KUOTA_ANGGARAN_OA,
                'label' => 'Kuota Anggaran & OA',
                'group' => 'other',
                'route_name' => 'admin.outline-agreements.index',
                'active_patterns' => ['admin.outline-agreements.*'],
            ],
            self::MENU_USER_PANEL => [
                'key' => self::MENU_USER_PANEL,
                'label' => 'User Panel',
                'group' => 'other',
                'route_name' => 'admin.user-panel.index',
                'active_patterns' => ['admin.user-panel.*'],
            ],
            self::MENU_UPLOAD_INFORMASI => [
                'key' => self::MENU_UPLOAD_INFORMASI,
                'label' => 'Upload Informasi',
                'group' => 'other',
                'route_name' => 'admin.information-upload.index',
                'active_patterns' => ['admin.information-upload.*'],
            ],
            self::MENU_STRUKTUR_ORGANISASI => [
                'key' => self::MENU_STRUKTUR_ORGANISASI,
                'label' => 'Struktur Organisasi',
                'group' => 'other',
                'route_name' => 'admin.structure.index',
                'active_patterns' => ['admin.structure.*'],
            ],
            self::MENU_KONTRAK_JASA_FABRIKASI_KONSTRUKSI => [
                'key' => self::MENU_KONTRAK_JASA_FABRIKASI_KONSTRUKSI,
                'label' => 'Kontrak Jasa Fabrikasi Konstruksi',
                'group' => 'other',
                'route_name' => 'admin.fabrication-construction-contracts.index',
                'active_patterns' => ['admin.fabrication-construction-contracts.*'],
            ],
        ];
    }

    /**
     * Get configurable admin menu items.
     *
     * @return list<array<string, mixed>>
     */
    public static function configurableItems(): array
    {
        return array_values(array_filter(
            static::definitions(),
            fn (array $item) => ($item['configurable'] ?? true) && ! ($item['super_admin_only'] ?? false) && ! ($item['always_visible'] ?? false),
        ));
    }

    /**
     * Determine if the admin user can access the menu.
     */
    public static function canAccess(?User $user, string $menuKey): bool
    {
        if (! $user || ! $user->hasRole(User::ROLE_ADMIN)) {
            return false;
        }

        $item = static::definitions()[$menuKey] ?? null;

        if (! $item) {
            return false;
        }

        if ($item['super_admin_only'] ?? false) {
            return $user->isSuperAdmin();
        }

        if (($item['always_visible'] ?? false) || $user->isSuperAdmin()) {
            return true;
        }

        if ($menuKey === self::MENU_ORDERS) {
            return static::canAccess($user, self::MENU_ORDER_JASA)
                || static::canAccess($user, self::MENU_ORDER_BENGKEL);
        }

        if (in_array($menuKey, [self::MENU_ORDER_JASA, self::MENU_ORDER_BENGKEL], true)) {
            if ($user->hasAdminMenuAccess($menuKey)) {
                return true;
            }

            $hasSplitOrderPermission = AdminRoleMenuAccess::query()
                ->where('admin_role', User::ADMIN_ROLE_ADMIN)
                ->whereIn('menu_key', [self::MENU_ORDER_JASA, self::MENU_ORDER_BENGKEL])
                ->exists();

            return ! $hasSplitOrderPermission
                && $user->hasAdminMenuAccess(self::MENU_ORDERS);
        }

        return $user->hasAdminMenuAccess($menuKey);
    }

    /**
     * Build sidebar data for the current admin user.
     *
     * @return array<string, mixed>
     */
    public static function sidebarForUser(?User $user): array
    {
        $items = [];
        $badgeCounts = $user && $user->hasRole(User::ROLE_ADMIN)
            ? app(AdminSidebarBadgeCounter::class)->counts($user)
            : [];

        foreach (static::definitions() as $item) {
            $resolvedChildren = [];

            foreach ($item['children'] ?? [] as $child) {
                $childKey = $child['key'] ?? null;

                if ($childKey && ! static::canAccess($user, $childKey)) {
                    continue;
                }

                $resolvedChildren[] = static::withBadge([
                    ...$child,
                    'href' => static::resolveUrl($child),
                    'active' => static::isItemActive($child),
                ], $badgeCounts);
            }

            if (! static::canAccess($user, $item['key']) && $resolvedChildren === []) {
                continue;
            }

            $resolved = $item;
            $resolved['href'] = static::resolveUrl($item);
            $resolved['active'] = static::isItemActive($item)
                || collect($resolvedChildren)->contains(fn (array $child): bool => $child['active'] ?? false);

            if ($resolvedChildren !== []) {
                $resolved['children'] = $resolvedChildren;
            }

            $items[$item['key']] = static::withBadge($resolved, $badgeCounts);
        }

        return [
            'dashboard' => $items[self::MENU_DASHBOARD] ?? null,
            'inventory' => $items[self::MENU_INVENTORY] ?? null,
            'orders' => $items[self::MENU_ORDERS] ?? null,
            'main' => array_values(array_filter(
                $items,
                fn (array $item) => $item['group'] === 'main'
                    && $item['key'] !== self::MENU_ORDERS
                    && ! ($item['sidebar_hidden'] ?? false),
            )),
            'support' => array_values(array_filter(
                $items,
                fn (array $item) => $item['group'] === 'support',
            )),
            'other' => array_values(array_filter(
                $items,
                fn (array $item) => $item['group'] === 'other',
            )),
        ];
    }

    /**
     * Resolve a route or fallback href.
     */
    public static function resolveUrl(array $item): string
    {
        $routeName = $item['route_name'] ?? null;

        if ($routeName && Route::has($routeName)) {
            return route($routeName);
        }

        return $item['href'] ?? '#';
    }

    /**
     * Attach a sidebar badge count when the mapped count is non-zero.
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, int>  $badgeCounts
     * @return array<string, mixed>
     */
    private static function withBadge(array $item, array $badgeCounts): array
    {
        $badgeKey = $item['badge_key'] ?? $item['key'] ?? null;

        if (! is_string($badgeKey)) {
            return $item;
        }

        $count = (int) ($badgeCounts[$badgeKey] ?? 0);

        if ($count > 0) {
            $item['badge_count'] = $count;
        }

        return $item;
    }

    /**
     * Determine if the menu item matches the current route.
     */
    public static function isItemActive(array $item): bool
    {
        foreach ($item['active_patterns'] ?? [] as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
