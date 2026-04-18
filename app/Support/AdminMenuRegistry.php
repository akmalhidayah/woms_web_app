<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminMenuRegistry
{
    public const MENU_DASHBOARD = 'dashboard';
    public const MENU_ORDERS = 'orders';
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
            self::MENU_ORDERS => [
                'key' => self::MENU_ORDERS,
                'label' => 'Order',
                'icon' => 'inbox',
                'group' => 'main',
                'route_name' => 'admin.orders.index',
                'active_patterns' => ['admin.orders.*'],
                'children' => [
                    [
                        'label' => 'Order Pekerjaan Jasa',
                        'route_name' => 'admin.orders.index',
                        'active_patterns' => ['admin.orders.index', 'admin.orders.show', 'admin.orders.edit', 'admin.orders.create', 'admin.orders.documents.*', 'admin.orders.scope-of-work.*'],
                    ],
                    [
                        'label' => 'Order Pekerjaan Bengkel',
                        'route_name' => 'admin.orders.workshop.index',
                        'active_patterns' => ['admin.orders.workshop.*'],
                    ],
                ],
            ],
            self::MENU_CREATE_HPP => [
                'key' => self::MENU_CREATE_HPP,
                'label' => 'Create HPP',
                'icon' => 'pencil',
                'group' => 'main',
                'route_name' => 'admin.hpp.index',
                'active_patterns' => ['admin.hpp.*'],
            ],
            self::MENU_VERIFIKASI_ANGGARAN => [
                'key' => self::MENU_VERIFIKASI_ANGGARAN,
                'label' => 'Verifikasi Anggaran',
                'icon' => 'wallet',
                'group' => 'main',
                'route_name' => 'admin.budget-verification.index',
                'active_patterns' => ['admin.budget-verification.*'],
            ],
            self::MENU_PURCHASE_ORDER => [
                'key' => self::MENU_PURCHASE_ORDER,
                'label' => 'Purchase Order',
                'icon' => 'list-checks',
                'group' => 'main',
                'route_name' => 'admin.purchase-order.index',
                'active_patterns' => ['admin.purchase-order.*'],
            ],
            self::MENU_LHPP_BAST => [
                'key' => self::MENU_LHPP_BAST,
                'label' => 'BAST',
                'icon' => 'file-text',
                'group' => 'main',
                'route_name' => 'admin.lhpp.index',
                'active_patterns' => ['admin.lhpp.*'],
            ],
            self::MENU_LPJ_PPL => [
                'key' => self::MENU_LPJ_PPL,
                'label' => 'LPJ / PPL',
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

        foreach (static::definitions() as $item) {
            if (! static::canAccess($user, $item['key'])) {
                continue;
            }

            $resolved = $item;
            $resolved['href'] = static::resolveUrl($item);
            $resolved['active'] = static::isItemActive($item);

            if (! empty($item['children'])) {
                $resolved['children'] = array_map(function (array $child): array {
                    return [
                        ...$child,
                        'href' => static::resolveUrl($child),
                        'active' => static::isItemActive($child),
                    ];
                }, $item['children']);
            }

            $items[$item['key']] = $resolved;
        }

        return [
            'dashboard' => $items[self::MENU_DASHBOARD] ?? null,
            'orders' => $items[self::MENU_ORDERS] ?? null,
            'main' => array_values(array_filter(
                $items,
                fn (array $item) => $item['group'] === 'main' && $item['key'] !== self::MENU_ORDERS,
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
