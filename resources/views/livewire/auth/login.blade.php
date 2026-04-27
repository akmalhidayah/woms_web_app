<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
public function login(): void
{
    $this->validate();

    $this->ensureIsNotRateLimited();

    if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    RateLimiter::clear($this->throttleKey());
    Session::regenerate();

    $this->redirectIntended(
        route(Auth::user()->dashboardRouteName(), absolute: false),
        navigate: true
    );
}

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="space-y-6">
    <x-auth-header
        title="Masuk ke WOMS"
        description="Masukkan akun Anda."
    />

    <x-auth-session-status class="auth-reveal auth-delay-1 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-left text-sm font-medium text-emerald-700" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div class="auth-reveal auth-delay-1 space-y-2">
            <label for="email" class="text-sm font-medium text-slate-700">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                class="auth-input"
            />
            @error('email')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-reveal auth-delay-2 space-y-2">
            <div class="flex items-center justify-between gap-4">
                <label for="password" class="text-sm font-medium text-slate-700">Password</label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link text-sm" wire:navigate>
                        Lupa password?
                    </a>
                @endif
            </div>

            <input
                wire:model="password"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan password"
                class="auth-input"
            />
            @error('password')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="auth-checkbox auth-reveal auth-delay-3">
            <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" />
            <span>Ingat sesi login saya</span>
        </label>

        <button
            type="submit"
            class="auth-button auth-reveal auth-delay-4"
        >
            Masuk
        </button>
    </form>

    <div class="auth-note auth-reveal auth-delay-5 text-center text-slate-600">
        Belum punya akun?
        <a href="{{ route('register') }}" class="auth-link" wire:navigate>Daftar sekarang</a>
    </div>
</div>
