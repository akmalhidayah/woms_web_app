<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRoleMenuAccess;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    /** @var list<string> */
    private const CONFIGURABLE_ADMIN_ROLES = [
        User::ADMIN_ROLE_ADMIN,
        User::ADMIN_ROLE_KARYAWAN,
    ];

    /**
     * Display the admin access control page.
     */
    public function index(): View
    {
        $storedMenuKeys = AdminRoleMenuAccess::query()
            ->whereIn('admin_role', self::CONFIGURABLE_ADMIN_ROLES)
            ->get(['admin_role', 'menu_key'])
            ->groupBy('admin_role');
        $menuKeysByRole = [];

        foreach (self::CONFIGURABLE_ADMIN_ROLES as $adminRole) {
            $menuKeysByRole[$adminRole] = $this->withLegacySplitPermissions(
                $storedMenuKeys->get($adminRole, collect())->pluck('menu_key'),
            )->all();
        }

        return view('admin.access-control.index', [
            'menuKeysByRole' => $menuKeysByRole,
            'configurableAdminRoles' => collect(User::adminRoleOptions())
                ->only(self::CONFIGURABLE_ADMIN_ROLES)
                ->all(),
            'menuOptions' => $this->roleMatrixItems(),
        ]);
    }

    /**
     * Update role-level menu access for all admin users.
     */
    public function update(Request $request): RedirectResponse
    {
        $usesRoleMatrix = $request->integer('permission_matrix_version') === 2;
        $submittedMenuKeys = $request->input('menu_keys', []);
        $roleMenuKeys = [];

        if ($usesRoleMatrix) {
            foreach (self::CONFIGURABLE_ADMIN_ROLES as $adminRole) {
                $roleMenuKeys[$adminRole] = is_array($submittedMenuKeys)
                    ? ($submittedMenuKeys[$adminRole] ?? [])
                    : $submittedMenuKeys;
            }
        } else {
            // Pertahankan kompatibilitas request lama yang mengirim menu_keys[] untuk role Admin.
            $roleMenuKeys[User::ADMIN_ROLE_ADMIN] = $submittedMenuKeys;
        }

        $validated = validator(['role_menu_keys' => $roleMenuKeys], [
            'role_menu_keys' => ['required', 'array'],
            'role_menu_keys.*' => ['array'],
            'role_menu_keys.*.*' => ['string', Rule::in(array_column(AdminMenuRegistry::configurableItems(), 'key'))],
        ])->validate();

        DB::transaction(function () use ($validated) {
            foreach ($validated['role_menu_keys'] as $adminRole => $menuKeys) {
                $this->syncRoleMenuAccess($adminRole, collect($menuKeys));
            }
        });

        return redirect()
            ->route('admin.access-control.index')
            ->with('status', $usesRoleMatrix
                ? 'Permission role Admin dan Karyawan berhasil diperbarui.'
                : 'Permission role Admin berhasil diperbarui.');
    }

    private function syncRoleMenuAccess(string $adminRole, Collection $menuKeys): void
    {
        $menuKeys = $menuKeys->filter()->unique()->values();
        $query = AdminRoleMenuAccess::query()->where('admin_role', $adminRole);

        if ($menuKeys->isEmpty()) {
            $query->delete();
        } else {
            $query->whereNotIn('menu_key', $menuKeys->all())->delete();
        }

        $existing = AdminRoleMenuAccess::query()
            ->where('admin_role', $adminRole)
            ->pluck('menu_key')
            ->all();
        $now = now();
        $newRows = $menuKeys
            ->reject(fn (string $key): bool => in_array($key, $existing, true))
            ->map(fn (string $key): array => [
                'admin_role' => $adminRole,
                'menu_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($newRows !== []) {
            AdminRoleMenuAccess::query()->insert($newRows);
        }
    }

    private function withLegacySplitPermissions(Collection $menuKeys): Collection
    {
        if ($menuKeys->contains(AdminMenuRegistry::MENU_ORDERS)
            && ! $menuKeys->contains(AdminMenuRegistry::MENU_ORDER_JASA)
            && ! $menuKeys->contains(AdminMenuRegistry::MENU_ORDER_BENGKEL)) {
            $menuKeys->push(AdminMenuRegistry::MENU_ORDER_JASA, AdminMenuRegistry::MENU_ORDER_BENGKEL);
        }

        if ($menuKeys->contains(AdminMenuRegistry::MENU_APPSHEET)
            && ! $menuKeys->contains(AdminMenuRegistry::MENU_APPSHEET_HISTORY_CONSUMABLE)
            && ! $menuKeys->contains(AdminMenuRegistry::MENU_APPSHEET_STOCK_CONSUMABLE)) {
            $menuKeys->push(
                AdminMenuRegistry::MENU_APPSHEET_HISTORY_CONSUMABLE,
                AdminMenuRegistry::MENU_APPSHEET_STOCK_CONSUMABLE,
            );
        }

        return $menuKeys->unique()->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function roleMatrixItems(): array
    {
        $definitions = AdminMenuRegistry::definitions();
        $configurableKeys = collect(AdminMenuRegistry::configurableItems())
            ->pluck('key')
            ->all();

        return collect($definitions)
            ->filter(fn (array $item): bool => ! ($item['access_control_hidden'] ?? false))
            ->filter(fn (array $item): bool => ! ($item['sidebar_hidden'] ?? false) || in_array($item['key'], $configurableKeys, true))
            ->map(function (array $item) use ($configurableKeys): array {
                return [
                    ...$item,
                    'admin_configurable' => in_array($item['key'], $configurableKeys, true),
                ];
            })
            ->values()
            ->all();
    }
}
