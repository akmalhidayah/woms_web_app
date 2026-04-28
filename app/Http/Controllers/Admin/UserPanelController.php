<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserPanelController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->string('role')->toString();
        $search = trim((string) $request->input('search', ''));

        $allowedRoles = User::roles();
        if (! in_array($role, $allowedRoles, true)) {
            $role = User::ROLE_USER;
        }

        $users = User::query()
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('nomor_hp', 'like', '%'.$search.'%')
                        ->orWhere('inisial', 'like', '%'.$search.'%')
                        ->orWhere('role', 'like', '%'.$search.'%')
                        ->orWhere('admin_role', 'like', '%'.$search.'%');
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.user-panel.index', [
            'users' => $users,
            'role' => $role,
            'search' => $search,
            'roleLabels' => User::roleLabels(),
            'adminRoleOptions' => User::adminRoleOptions(),
            'summaryCounts' => collect(User::roleLabels())->mapWithKeys(
                fn (string $label, string $value) => [$value => User::where('role', $value)->count()]
            ),
            'initialCreateModal' => [
                'open' => session('user_panel_modal') === 'create',
                'form' => [
                    'name' => (string) old('name', ''),
                    'email' => (string) old('email', ''),
                    'nomor_hp' => (string) old('nomor_hp', ''),
                    'inisial' => (string) old('inisial', ''),
                    'role' => (string) old('role', $role),
                    'admin_role' => (string) old('admin_role', User::ADMIN_ROLE_ADMIN),
                ],
            ],
            'initialEditModal' => [
                'open' => session('user_panel_modal') === 'edit',
                'action' => session('user_panel_edit_action', ''),
                'form' => [
                    'name' => (string) old('name', ''),
                    'email' => (string) old('email', ''),
                    'nomor_hp' => (string) old('nomor_hp', ''),
                    'inisial' => (string) old('inisial', ''),
                    'role' => (string) old('role', $role),
                    'admin_role' => (string) old('admin_role', User::ADMIN_ROLE_ADMIN),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canManageRequestedRole($request->user(), $request->input('role'))) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $request->input('role', User::ROLE_USER)])
                ->with('error', 'Anda tidak memiliki izin untuk membuat user dengan tipe tersebut.');
        }

        $validator = Validator::make($request->all(), $this->rules($request));

        if ($validator->fails()) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $request->input('role', User::ROLE_USER)])
                ->withErrors($validator)
                ->withInput()
                ->with('user_panel_modal', 'create');
        }

        $validated = $validator->validated();

        User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nomor_hp' => $this->nullableTrim($validated['nomor_hp'] ?? null),
            'inisial' => $this->nullableTrim($validated['inisial'] ?? null),
            'role' => $validated['role'],
            'admin_role' => $validated['role'] === User::ROLE_ADMIN ? $validated['admin_role'] : null,
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('admin.user-panel.index', ['role' => $validated['role']])
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $this->canManageExistingUser($request->user(), $user)) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $user->role])
                ->with('error', 'Anda tidak memiliki izin untuk mengubah user ini.');
        }

        if (! $this->canManageRequestedRole($request->user(), $request->input('role', $user->role))) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $user->role])
                ->with('error', 'Anda tidak memiliki izin untuk mengubah user ke tipe tersebut.');
        }

        if ($request->user()->isSuperAdmin() && $request->user()->is($user)) {
            $requestedRole = $request->input('role', $user->role);
            $requestedAdminRole = $request->input('admin_role', $user->resolvedAdminRole());

            if ($requestedRole !== User::ROLE_ADMIN || $requestedAdminRole !== User::ADMIN_ROLE_SUPER_ADMIN) {
                return redirect()
                    ->route('admin.user-panel.index', ['role' => $user->role])
                    ->with('error', 'Super Admin aktif tidak dapat mengubah dirinya menjadi non-super admin.');
            }
        }

        $validator = Validator::make($request->all(), $this->rules($request, $user));

        if ($validator->fails()) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $request->input('role', $user->role)])
                ->withErrors($validator)
                ->withInput()
                ->with('user_panel_modal', 'edit')
                ->with('user_panel_edit_action', route('admin.user-panel.update', $user));
        }

        $validated = $validator->validated();
        $newRole = $validated['role'];

        $user->name = trim($validated['name']);
        $user->email = strtolower(trim($validated['email']));
        $user->nomor_hp = $this->nullableTrim($validated['nomor_hp'] ?? null);
        $user->inisial = $this->nullableTrim($validated['inisial'] ?? null);
        $user->role = $newRole;
        $user->admin_role = $newRole === User::ROLE_ADMIN ? $validated['admin_role'] : null;

        $user->save();

        if ($newRole !== User::ROLE_ADMIN) {
            $user->adminMenuAccesses()->delete();
        }

        return redirect()
            ->route('admin.user-panel.index', ['role' => $newRole])
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $user->role])
                ->with('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
        }

        if (! $this->canManageExistingUser($request->user(), $user)) {
            return redirect()
                ->route('admin.user-panel.index', ['role' => $user->role])
                ->with('error', 'Anda tidak memiliki izin untuk menghapus user ini.');
        }

        $role = $user->role;
        $user->adminMenuAccesses()->delete();
        $user->delete();

        return redirect()
            ->route('admin.user-panel.index', ['role' => $role])
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?User $user = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($user) {
            $emailRule = $emailRule->ignore($user->id);
        }

        $isCreate = $user === null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'inisial' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(User::roles())],
            'admin_role' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_ADMIN),
                'nullable',
                Rule::in(array_keys(User::adminRoleOptions())),
            ],
        ];

        if ($isCreate) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function canManageRequestedRole(User $actor, ?string $requestedRole): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $requestedRole !== User::ROLE_ADMIN;
    }

    private function canManageExistingUser(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $target->role !== User::ROLE_ADMIN;
    }
}
