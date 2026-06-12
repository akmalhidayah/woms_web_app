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

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
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
        ]);

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
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Profile" subheading="Update your profile and password in one page">
        <div class="space-y-5">
            <form wire:submit="updateProfileInformation" class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-800 text-white">
                        <i data-lucide="user-round" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Informasi Profil</h2>
                        <p class="text-xs text-slate-500">Ubah nama dan email akun.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" />

                    <div>
                        <flux:input wire:model="email" label="{{ __('Email') }}" type="email" name="email" required autocomplete="email" />

                        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                            <div>
                                <p class="mt-2 text-sm text-gray-800">
                                    {{ __('Your email address is unverified.') }}

                                    <button
                                        wire:click.prevent="resendVerificationNotification"
                                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        {{ __('Click here to re-send the verification email.') }}
                                    </button>
                                </p>

                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-2 text-sm font-medium text-green-600">
                                        {{ __('A new verification link has been sent to your email address.') }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-4">
                    <flux:button variant="primary" type="submit">{{ __('Save Profile') }}</flux:button>

                    <x-action-message class="me-3" on="profile-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>

            <form wire:submit="updatePassword" class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-800 text-white">
                        <i data-lucide="lock-keyhole" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Update Password</h2>
                        <p class="text-xs text-slate-500">Masukkan password saat ini sebelum membuat password baru.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <flux:input
                        wire:model="current_password"
                        id="update_password_current_password"
                        label="{{ __('Current password') }}"
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                    />
                    <flux:input
                        wire:model="password"
                        id="update_password_password"
                        label="{{ __('New password') }}"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                    />
                    <flux:input
                        wire:model="password_confirmation"
                        id="update_password_password_confirmation"
                        label="{{ __('Confirm Password') }}"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>

                <div class="mt-5 flex items-center gap-4">
                    <flux:button variant="primary" type="submit">{{ __('Update Password') }}</flux:button>

                    <x-action-message class="me-3" on="password-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>

            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <livewire:settings.delete-user-form />
            </div>
        </div>
    </x-settings.layout>
</section>
