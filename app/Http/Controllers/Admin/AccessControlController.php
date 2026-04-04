<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AccessControlController extends Controller
{
    /**
     * Display the admin access control page.
     */
    public function index(): View
    {
        $adminUsers = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->with('adminMenuAccesses')
            ->orderByRaw("case when admin_role = 'super_admin' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        return view('admin.access-control.index', [
            'adminUsers' => $adminUsers,
            'menuOptions' => AdminMenuRegistry::configurableItems(),
            'adminRoleOptions' => User::adminRoleOptions(),
        ]);
    }

    /**
     * Update admin subrole and menu access.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole(User::ROLE_ADMIN), 404);

        $validated = $request->validate([
            'admin_role' => ['required', Rule::in(array_keys(User::adminRoleOptions()))],
            'menu_keys' => ['nullable', 'array'],
            'menu_keys.*' => ['string', Rule::in(array_column(AdminMenuRegistry::configurableItems(), 'key'))],
        ]);

        if ($request->user()?->is($user) && $validated['admin_role'] !== User::ADMIN_ROLE_SUPER_ADMIN) {
            return redirect()
                ->route('admin.access-control.index')
                ->with('status', 'Super admin yang sedang login tidak bisa menurunkan role dirinya sendiri.');
        }

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'admin_role' => $validated['admin_role'],
            ]);

            if ($validated['admin_role'] === User::ADMIN_ROLE_SUPER_ADMIN) {
                $user->adminMenuAccesses()->delete();
                return;
            }

            $menuKeys = collect($validated['menu_keys'] ?? [])
                ->filter()
                ->unique()
                ->values();

            $user->adminMenuAccesses()
                ->whereNotIn('menu_key', $menuKeys->all())
                ->delete();

            $existing = $user->adminMenuAccesses()
                ->pluck('menu_key')
                ->all();

            $newRows = $menuKeys
                ->reject(fn (string $key) => in_array($key, $existing, true))
                ->map(fn (string $key) => ['menu_key' => $key])
                ->all();

            if ($newRows !== []) {
                $user->adminMenuAccesses()->createMany($newRows);
            }
        });

        return redirect()
            ->route('admin.access-control.index')
            ->with('status', 'Hak akses admin berhasil diperbarui.');
    }
}
