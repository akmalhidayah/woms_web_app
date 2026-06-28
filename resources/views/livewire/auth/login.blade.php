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
        $this->email = strtolower(trim($this->email));

        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectRoute(Auth::user()->dashboardRouteName(), navigate: true);
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

<div class="space-y-6" data-login-form>
    <style>
        [data-login-form] .login-field-control {
            -webkit-text-size-adjust: 100%;
        }

        [data-login-form] .login-submit-button {
            min-height: 48px;
        }

        [data-login-form] .login-field-label {
            display: inline-flex;
            align-items: center;
            padding-inline: 0.125rem;
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.15;
            color: #334155;
        }

        [data-login-form] .login-field-link {
            display: inline-flex;
            align-items: center;
            padding-inline: 0.125rem;
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.15;
        }

        [data-login-form] .login-submit-button:disabled {
            cursor: not-allowed;
            opacity: 0.78;
            transform: none;
        }

        [data-login-form] .login-spinner {
            height: 1rem;
            width: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #ffffff;
            border-radius: 9999px;
            animation: login-spin 700ms linear infinite;
        }

        @keyframes login-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 640px) {
            [data-login-form] .login-field-control {
                font-size: 16px;
                line-height: 1.5;
            }
        }
    </style>

    <x-auth-header
        title="Masuk ke WOMS"
        description=""
    />

    <x-auth-session-status class="auth-reveal auth-delay-1 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-left text-sm font-medium text-emerald-700" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div class="auth-reveal auth-delay-1 space-y-2.5">
            <label for="email" class="login-field-label">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                class="auth-input login-field-control"
            />
            @error('email')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-reveal auth-delay-2 space-y-2.5" x-data="{ showPassword: false }">
            <div class="flex items-center justify-between gap-4">
                <label for="password" class="login-field-label">Password</label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link login-field-link" wire:navigate>
                        Lupa password?
                    </a>
                @endif
            </div>

            <div class="relative">
                <input
                    wire:model="password"
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="auth-input login-field-control pr-12"
                />

                <button
                    type="button"
                    class="absolute inset-y-0 right-3 my-auto inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-200"
                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    @click="showPassword = !showPassword"
                >
                    <svg x-show="!showPassword" class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-show="showPassword" class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.7 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a17.9 17.9 0 0 1-2.1 3.1" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.6 6.6C3.6 8.6 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 2l20 20" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 9.9A3 3 0 0 0 14.1 14.1" />
                    </svg>
                </button>
            </div>

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
            class="auth-button auth-reveal auth-delay-4 login-submit-button gap-2 text-base sm:text-sm"
            wire:loading.attr="disabled"
            wire:target="login"
        >
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading.flex wire:target="login" class="items-center gap-2">
                <span class="login-spinner" aria-hidden="true"></span>
                <span>Memproses...</span>
            </span>
        </button>
    </form>

    {{-- REGISTRATION_DISABLED: tombol daftar dinonaktifkan sementara.
         Aktifkan lagi setelah route register di routes/auth.php dibuka. --}}
    {{-- <div class="auth-note auth-reveal auth-delay-5 text-center text-slate-600">
        Belum punya akun?
        <a href="{{ route('register') }}" class="auth-link" wire:navigate>Daftar sekarang</a>
    </div> --}}
</div>
