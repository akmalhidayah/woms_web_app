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

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $users = User::query()
            ->where('role', User::ROLE_PKM)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('nomor_hp', 'like', '%'.$search.'%')
                        ->orWhere('inisial', 'like', '%'.$search.'%');
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pkm.user-panel.index', [
            'users' => $users,
            'search' => $search,
            'defaultPassword' => self::DEFAULT_NEW_USER_PASSWORD,
            'initialCreateModal' => [
                'open' => session('pkm_user_panel_modal') === 'create',
                'form' => $this->oldForm(),
            ],
            'initialEditModal' => [
                'open' => session('pkm_user_panel_modal') === 'edit',
                'action' => session('pkm_user_panel_edit_action', ''),
                'form' => $this->oldForm(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return redirect()->route('pkm.user-panel.index')
                ->withErrors($validator)->withInput()->with('pkm_user_panel_modal', 'create');
        }

        $validated = $validator->validated();
        User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nomor_hp' => $this->nullableTrim($validated['nomor_hp'] ?? null),
            'inisial' => $this->nullableTrim($validated['inisial'] ?? null),
            'role' => User::ROLE_PKM,
            'admin_role' => null,
            'password' => self::DEFAULT_NEW_USER_PASSWORD,
        ]);

        return redirect()->route('pkm.user-panel.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $returnTo = $this->returnRouteParameters($request);

        if (! $this->canManageUser($user)) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->with('error', 'Anda tidak memiliki izin untuk mengubah user ini.');
        }

        $validator = Validator::make($request->all(), $this->rules($user));
        if ($validator->fails()) {
            return redirect()->route('pkm.user-panel.index', $returnTo)
                ->withErrors($validator)->withInput()
                ->with('pkm_user_panel_modal', 'edit')
                ->with('pkm_user_panel_edit_action', route('pkm.user-panel.update', $user));
        }

        $validated = $validator->validated();
        $user->fill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'nomor_hp' => $this->nullableTrim($validated['nomor_hp'] ?? null),
            'inisial' => $this->nullableTrim($validated['inisial'] ?? null),
            'role' => User::ROLE_PKM,
            'admin_role' => null,
        ])->save();

        return redirect()->route('pkm.user-panel.index', $returnTo)->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $returnTo = $this->returnRouteParameters($request);

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
                ->with('error', "Pengguna masih terdaftar pada alur approval {$documentName} yang belum selesai.");
        }

        $user->adminMenuAccesses()->delete();
        $user->delete();

        return redirect()->route('pkm.user-panel.index', $returnTo)->with('success', 'Pengguna berhasil dihapus.');
    }

    private function rules(?User $user = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($user) {
            $emailRule = $emailRule->ignore($user->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'inisial' => ['nullable', 'string', 'max:20'],
            'role' => ['prohibited'],
        ];
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function canManageUser(User $user): bool
    {
        return $user->role === User::ROLE_PKM;
    }

    private function activeApprovalDocument(User $user): ?string
    {
        foreach ([
            'HPP' => HppSignature::class,
            'Initial Work' => InitialWorkSignature::class,
            'Quality Control' => QualityControlSignature::class,
            'BAST' => LhppBastSignature::class,
        ] as $documentName => $model) {
            if ($model::query()->where('signer_user_id', $user->id)
                ->whereIn('status', [$model::STATUS_LOCKED, $model::STATUS_PENDING])->exists()) {
                return $documentName;
            }
        }

        return null;
    }

    private function returnRouteParameters(Request $request): array
    {
        return array_filter([
            'search' => trim((string) $request->input('_return_search', '')) ?: null,
            'page' => $request->integer('_return_page') > 1 ? $request->integer('_return_page') : null,
        ]);
    }

    private function oldForm(): array
    {
        return [
            'name' => (string) old('name', ''),
            'email' => (string) old('email', ''),
            'nomor_hp' => (string) old('nomor_hp', ''),
            'inisial' => (string) old('inisial', ''),
        ];
    }
}
