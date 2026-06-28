<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.user')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $nomor_hp = '';

    public string $inisial = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $roleLabel = '';

    public bool $showsOrganizationPositions = false;

    /**
     * @var list<array{label: string, value: string, meta: ?string}>
     */
    public array $organizationPositions = [];

    public bool $usesDefaultPassword = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->nomor_hp = (string) ($user->nomor_hp ?? '');
        $this->inisial = (string) ($user->inisial ?? '');
        $this->roleLabel = User::roleLabels()[$user->role] ?? ucfirst((string) $user->role);
        $this->showsOrganizationPositions = in_array($user->role, [User::ROLE_USER, User::ROLE_APPROVER], true);
        $this->organizationPositions = $this->resolveOrganizationPositions($user);
        $this->usesDefaultPassword = Hash::check('bengkelmesin123', (string) $user->password);
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'nomor_hp' => ['nullable', 'string', 'max:30'],

            'inisial' => ['nullable', 'string', 'max:20'],
        ]);

        $validated['email'] = strtolower(trim($validated['email']));
        $validated['name'] = trim($validated['name']);
        $validated['nomor_hp'] = filled($validated['nomor_hp'] ?? null) ? trim((string) $validated['nomor_hp']) : null;
        $validated['inisial'] = filled($validated['inisial'] ?? null) ? strtoupper(trim((string) $validated['inisial'])) : null;

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->usesDefaultPassword = false;

        $this->dispatch('password-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard'));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * @return list<array{label: string, value: string, meta: ?string}>
     */
    private function resolveOrganizationPositions(User $user): array
    {
        $user->loadMissing([
            'headedDepartments',
            'seniorManagedUnits.department',
            'managedSections.unitWork.department',
        ]);

        $positions = [];

        foreach ($user->headedDepartments as $department) {
            $positions[] = [
                'label' => 'General Manager',
                'value' => (string) $department->name,
                'meta' => 'Department',
            ];
        }

        foreach ($user->seniorManagedUnits as $unit) {
            $positions[] = [
                'label' => 'Senior Manager',
                'value' => (string) $unit->name,
                'meta' => $unit->department?->name,
            ];
        }

        foreach ($user->managedSections as $section) {
            $positions[] = [
                'label' => 'Manager',
                'value' => (string) $section->name,
                'meta' => $section->unitWork?->name,
            ];
        }

        return $positions;
    }
}; ?>

<section class="profile-zoom-safe mx-auto max-w-4xl space-y-4">
    <section class="flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 sm:px-5 sm:py-4">
        <div class="flex min-w-0 items-center gap-3">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#7f1017] text-white shadow-sm">
                <i data-lucide="user-round-cog" class="h-5 w-5"></i>
            </span>
            <div class="min-w-0">
                <h1 class="truncate text-lg font-bold text-slate-900">Profil Pengguna</h1>
            </div>
        </div>
        <span class="shrink-0 rounded-lg bg-[#7f1017] px-3 py-1.5 text-xs font-semibold text-white">{{ $roleLabel }}</span>
    </section>

    <section class="grid gap-4 lg:grid-cols-[220px_1fr]">
        <aside class="rounded-xl border border-red-100 bg-red-50/55 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[#7f1017] text-xl font-bold text-white">
                    {{ auth()->user()?->initials() }}
                </div>
                <div class="min-w-0">
                    <h2 class="truncate font-bold text-slate-900">{{ auth()->user()?->name }}</h2>
                    <p class="mt-1 break-all text-xs text-slate-500">{{ auth()->user()?->email }}</p>
                </div>
            </div>

            <div class="mt-4 border-t border-red-100 pt-4 text-xs leading-5 text-slate-500">
                Informasi akun yang tersimpan saat ini.
            </div>

            @if ($showsOrganizationPositions && $organizationPositions !== [])
                <div class="mt-4 space-y-2 border-t border-red-100 pt-4">
                    <div class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Jabatan</div>
                    @foreach ($organizationPositions as $position)
                        <div class="rounded-lg border border-red-100 bg-white px-3 py-2">
                            <div class="text-xs font-bold text-slate-900">{{ $position['label'] }}</div>
                            <div class="mt-0.5 text-xs text-slate-600">{{ $position['value'] }}</div>
                            @if ($position['meta'])
                                <div class="mt-0.5 text-[11px] text-slate-400">{{ $position['meta'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>

        <form wire:submit="updateProfileInformation" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#7f1017] text-white">
                    <i data-lucide="user-round" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Informasi Profil</h2>
                    <p class="text-xs text-slate-500">Data lama otomatis terisi dari akun saat ini.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama lengkap</label>
                    <input id="name" wire:model="name" type="text" name="name" value="{{ $name }}" required autofocus autocomplete="name"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email</label>
                    <input id="email" wire:model="email" type="email" name="email" value="{{ $email }}" required autocomplete="email"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                        <p class="mt-2 text-xs text-slate-600">
                            Email belum terverifikasi.
                            <button wire:click.prevent="resendVerificationNotification" class="font-semibold text-[#7f1017] underline">
                                Kirim ulang email verifikasi.
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-xs font-medium text-emerald-600">Link verifikasi baru sudah dikirim.</p>
                        @endif
                    @endif
                </div>

                <div>
                    <label for="nomor_hp" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor HP</label>
                    <input id="nomor_hp" wire:model="nomor_hp" type="text" name="nomor_hp" value="{{ $nomor_hp }}" autocomplete="tel"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                    <p class="mt-1.5 text-xs leading-5 text-slate-500">Mohon isi nomor HP aktif untuk kebutuhan notifikasi dan konfirmasi approval.</p>
                    @error('nomor_hp') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="inisial" class="mb-1.5 block text-xs font-semibold text-slate-700">Inisial</label>
                    <input id="inisial" wire:model="inisial" type="text" name="inisial" value="{{ $inisial }}" maxlength="20"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm uppercase text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                    @error('inisial') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-4">
                <x-action-message class="text-sm text-emerald-600" on="profile-updated">
                    Tersimpan.
                </x-action-message>

                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#7f1017] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#6f0d13]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>

    <section class="grid gap-4 lg:grid-cols-[220px_1fr]">
        <aside class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-800 text-white">
                <i data-lucide="lock-keyhole" class="h-5 w-5"></i>
            </div>
            <h2 class="mt-3 font-bold text-slate-900">Update Password</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Password lama tidak ditampilkan. Masukkan password saat ini untuk mengganti password.</p>
        </aside>

        <form wire:submit="updatePassword" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @if ($usesDefaultPassword)
                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <div class="font-semibold">Password default masih digunakan.</div>
                    <p class="mt-1 text-xs leading-5">Harap mengubah password default Anda untuk menjaga keamanan akun.</p>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="update_password_current_password" class="mb-1.5 block text-xs font-semibold text-slate-700">Password saat ini</label>
                    <input wire:model="current_password" id="update_password_current_password" type="password" name="current_password" required autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                    @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="update_password_password" class="mb-1.5 block text-xs font-semibold text-slate-700">Password baru</label>
                    <input wire:model="password" id="update_password_password" type="password" name="password" required autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="update_password_password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">Konfirmasi password baru</label>
                    <input wire:model="password_confirmation" id="update_password_password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#7f1017] focus:ring-2 focus:ring-red-100">
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-4">
                <x-action-message class="text-sm text-emerald-600" on="password-updated">
                    Tersimpan.
                </x-action-message>

                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900">
                    <i data-lucide="key-round" class="h-4 w-4"></i>
                    Update Password
                </button>
            </div>
        </form>
    </section>
</section>
