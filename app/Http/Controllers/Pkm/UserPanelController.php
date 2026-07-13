<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserPanelController extends Controller
{
    private const DEFAULT_NEW_USER_PASSWORD = 'bengkelmesin123';

    private const MANAGEABLE_ROLES = [
        User::ROLE_PKM,
        User::ROLE_APPROVER,
    ];

    private const ROLE_LABELS = [
        User::ROLE_PKM => 'PKM',
        User::ROLE_APPROVER => 'Approval',
    ];

    public function index(Request $request): View
    {
        $role = $this->validRole($request->string('role')->toString());
        $search = trim((string) $request->input('search', ''));

        $users = User::query()
            ->whereIn('role', self::MANAGEABLE_ROLES)
            ->where('role', $role)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('nomor_hp', 'like', '%'.$search.'%')
                        ->orWhere('inisial', 'like', '%'.$search.'%')
                        ->orWhere('role', 'like', '%'.$search.'%');
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $summaryCounts = User::query()
            ->whereIn('role', self::MANAGEABLE_ROLES)
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return view('pkm.user-panel.index', [
            'users' => $users,
            'role' => $role,
            'search' => $search,
            'roleLabels' => self::ROLE_LABELS,
            'defaultPassword' => self::DEFAULT_NEW_USER_PASSWORD,
            'summaryCounts' => collect(self::ROLE_LABELS)->mapWithKeys(
                fn (string $label, string $value): array => [$value => (int) ($summaryCounts[$value] ?? 0)]
            ),
            'initialCreateModal' => [
                'open' => session('pkm_user_panel_modal') === 'create',
                'form' => [
                    'name' => (string) old('name', ''),
                    'email' => (string) old('email', ''),
                    'nomor_hp' => (string) old('nomor_hp', ''),
                    'inisial' => (string) old('inisial', ''),
                    'role' => $this->validRole((string) old('role', $role)),
                ],
            ],
            'initialEditModal' => [
                'open' => session('pkm_user_panel_modal') === 'edit',
                'action' => session('pkm_user_panel_edit_action', ''),
                'form' => [
                    'name' => (string) old('name', ''),
                    'email' => (string) old('email', ''),
                    'nomor_hp' => (string) old('nomor_hp', ''),
                    'inisial' => (string) old('inisial', ''),
                    'role' => $this->validRole((string) old('role', $role)),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()
                ->route('pkm.user-panel.index', ['role' => $this->validRole((string) $request->input('role'))])
                ->withErrors($validator)
                ->withInput()
                ->with('pkm_user_panel_modal', 'create');
        }

        $validated = $validator->validated();

        User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nomor_hp' => $this->nullableTrim($validated['nomor_hp'] ?? null),
            'inisial' => $this->nullableTrim($validated['inisial'] ?? null),
            'role' => $validated['role'],
            'admin_role' => null,
            'password' => self::DEFAULT_NEW_USER_PASSWORD,
        ]);

        return redirect()
            ->route('pkm.user-panel.index', ['role' => $validated['role']])
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $returnTo = $this->returnRouteParameters($request, $user->role);

        if (! $this->canManageUser($user)) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->with('error', 'Anda tidak memiliki izin untuk mengubah user ini.');
        }

        if ($request->user()->is($user) && $request->input('role') !== User::ROLE_PKM) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->with('error', 'Akun PKM yang sedang digunakan tidak dapat diubah menjadi Approval.');
        }

        $validator = Validator::make($request->all(), $this->rules($user));

        if ($validator->fails()) {
            return redirect()
                ->route('pkm.user-panel.index', $returnTo)
                ->withErrors($validator)
                ->withInput()
                ->with('pkm_user_panel_modal', 'edit')
                ->with('pkm_user_panel_edit_action', route('pkm.user-panel.update', $user));
        }

        $validated = $validator->validated();
        $user->fill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nomor_hp' => $this->nullableTrim($validated['nomor_hp'] ?? null),
            'inisial' => $this->nullableTrim($validated['inisial'] ?? null),
            'role' => $validated['role'],
            'admin_role' => null,
        ])->save();

        $user->adminMenuAccesses()->delete();

        return redirect()->route('pkm.user-panel.index', $returnTo)
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $returnTo = $this->returnRouteParameters($request, $user->role);

        if ($request->user()->is($user)) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->with('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
        }

        if (! $this->canManageUser($user)) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->with('error', 'Anda tidak memiliki izin untuk menghapus user ini.');
        }

        if ($documentName = $this->activeApprovalDocument($user)) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->with('error', sprintf(
                    'Pengguna masih terdaftar pada alur approval %s yang belum selesai.',
                    $documentName,
                ));
        }

        $user->adminMenuAccesses()->delete();
        $user->delete();

        return redirect()->route('pkm.user-panel.index', $returnTo)
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function rules(?User $user = null): array
    {
        $emailRule = Rule::unique('users', 'email');

        if ($user) {
            $emailRule->ignore($user->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'inisial' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
        ];
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function canManageUser(User $user): bool
    {
        return in_array($user->role, self::MANAGEABLE_ROLES, true);
    }

    private function activeApprovalDocument(User $user): ?string
    {
        $approvalModels = [
            'HPP' => HppSignature::class,
            'Initial Work' => InitialWorkSignature::class,
            'Quality Control' => QualityControlSignature::class,
            'BAST' => LhppBastSignature::class,
        ];

        foreach ($approvalModels as $documentName => $model) {
            if ($model::query()
                ->where('signer_user_id', $user->id)
                ->whereIn('status', [$model::STATUS_LOCKED, $model::STATUS_PENDING])
                ->exists()) {
                return $documentName;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function returnRouteParameters(Request $request, string $fallbackRole): array
    {
        $role = $this->validRole((string) $request->input('_return_role', $fallbackRole));

        return array_filter([
            'role' => $role,
            'search' => trim((string) $request->input('_return_search', '')) ?: null,
            'page' => $request->integer('_return_page') > 1 ? $request->integer('_return_page') : null,
        ]);
    }

    private function validRole(string $role): string
    {
        return in_array($role, self::MANAGEABLE_ROLES, true) ? $role : User::ROLE_PKM;
    }
}
