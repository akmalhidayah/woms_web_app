<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    private const MAX_RESET_LINK_ATTEMPTS = 3;
    private const RESET_LINK_DECAY_SECONDS = 300;

    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_RESET_LINK_ATTEMPTS)) {
            $this->addError('email', $this->tooManyAttemptsMessage(RateLimiter::availableIn($this->throttleKey())));

            return;
        }

        RateLimiter::hit($this->throttleKey(), self::RESET_LINK_DECAY_SECONDS);

        try {
            $status = Password::sendResetLink($this->only('email'));
        } catch (\Throwable $exception) {
            Log::error('Password reset email failed to send.', [
                'email' => $this->email,
                'message' => $exception->getMessage(),
            ]);

            $this->addError('email', 'Email reset belum bisa dikirim. Periksa konfigurasi SMTP.');

            return;
        }

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('status', 'Link reset password sudah dikirim ke email.');

            return;
        }

        $this->addError('email', $this->message($status));
    }

    private function throttleKey(): string
    {
        return 'password-reset:'.Str::transliterate($this->email.'|'.request()->ip());
    }

    private function tooManyAttemptsMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return "Terlalu banyak permintaan reset password. Coba lagi dalam {$minutes} menit.";
    }

    private function message(string $status): string
    {
        return match ($status) {
            Password::RESET_THROTTLED => 'Permintaan reset terlalu sering. Silakan tunggu sebentar.',
            Password::INVALID_USER => 'Email tidak terdaftar.',
            default => 'Reset password belum bisa diproses.',
        };
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Lupa password" description="Masukkan email akun Anda untuk menerima link reset password." />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <div class="grid gap-2">
            <flux:input wire:model="email" label="Email" type="email" name="email" required autofocus placeholder="email@example.com" />
        </div>

        <flux:button variant="primary" type="submit" class="w-full">Kirim link reset</flux:button>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-400">
        Kembali ke
        <x-text-link href="{{ route('login') }}">halaman login</x-text-link>
    </div>
</div>
