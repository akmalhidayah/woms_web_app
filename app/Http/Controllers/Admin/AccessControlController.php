<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRoleMenuAccess;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    /**
     * Display the admin access control page.
     */
    public function index(): View
    {
        return view('admin.access-control.index', [
            'adminMenuKeys' => AdminRoleMenuAccess::query()
                ->where('admin_role', User::ADMIN_ROLE_ADMIN)
                ->pluck('menu_key')
                ->all(),
            'menuOptions' => $this->roleMatrixItems(),
        ]);
    }

    /**
     * Update role-level menu access for all admin users.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_keys' => ['nullable', 'array'],
            'menu_keys.*' => ['string', Rule::in(array_column(AdminMenuRegistry::configurableItems(), 'key'))],
        ]);

        DB::transaction(function () use ($validated) {
            $menuKeys = collect($validated['menu_keys'] ?? [])
                ->filter()
                ->unique()
                ->values();

            AdminRoleMenuAccess::query()
                ->where('admin_role', User::ADMIN_ROLE_ADMIN)
                ->whereNotIn('menu_key', $menuKeys->all())
                ->delete();

            $existing = AdminRoleMenuAccess::query()
                ->where('admin_role', User::ADMIN_ROLE_ADMIN)
                ->pluck('menu_key')
                ->all();

            $newRows = $menuKeys
                ->reject(fn (string $key) => in_array($key, $existing, true))
                ->map(fn (string $key): array => [
                    'admin_role' => User::ADMIN_ROLE_ADMIN,
                    'menu_key' => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            if ($newRows !== []) {
                AdminRoleMenuAccess::query()->insert($newRows);
            }
        });

        return redirect()
            ->route('admin.access-control.index')
            ->with('status', 'Permission role Admin berhasil diperbarui.');
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
