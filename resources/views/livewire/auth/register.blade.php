<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $role = User::ROLE_USER;
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'role' => ['required', 'string', Rule::in(User::roles())],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route($user->dashboardRouteName(), absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-auth-header
        title="Buat akun baru"
        description="Lengkapi data akun."
    />

    <x-auth-session-status class="auth-reveal auth-delay-1 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-left text-sm font-medium text-emerald-700" :status="session('status')" />

    <form wire:submit="register" class="space-y-5">
        <div class="auth-reveal auth-delay-1 space-y-2">
            <label for="name" class="text-sm font-medium text-slate-700">Nama lengkap</label>
            <input
                wire:model="name"
                id="name"
                type="text"
                name="name"
                required
                autofocus
                autocomplete="name"
                placeholder="Masukkan nama lengkap"
                class="auth-input"
            />
            @error('name')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-reveal auth-delay-2 space-y-2">
            <label for="email" class="text-sm font-medium text-slate-700">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
                class="auth-input"
            />
            @error('email')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-reveal auth-delay-2 space-y-2">
            <label for="role" class="text-sm font-medium text-slate-700">Role / usertype</label>
            <select
                wire:model="role"
                id="role"
                name="role"
                required
                class="auth-input"
            >
                <option value="admin">Admin</option>
                <option value="user">User</option>
                <option value="pkm">PKM</option>
                <option value="approver">Approver</option>
            </select>
            @error('role')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-reveal auth-delay-3 space-y-2">
            <label for="password" class="text-sm font-medium text-slate-700">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Minimal sesuai aturan password Laravel"
                class="auth-input"
            />
            @error('password')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-reveal auth-delay-4 space-y-2">
            <label for="password_confirmation" class="text-sm font-medium text-slate-700">Konfirmasi password</label>
            <input
                wire:model="password_confirmation"
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Ulangi password"
                class="auth-input"
            />
            @error('password_confirmation')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="auth-button auth-reveal auth-delay-5"
        >
            Buat akun
        </button>
    </form>

    <div class="auth-note auth-reveal auth-delay-5 text-center text-slate-600">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="auth-link" wire:navigate>Masuk di sini</a>
    </div>
</div>
