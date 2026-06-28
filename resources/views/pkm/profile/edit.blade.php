<x-layouts.pkm title="Profil PKM">
    @php
        $user = auth()->user();
    @endphp

    @if (session('success'))
        <div id="profile-success" data-message="{{ session('success') }}" class="hidden"></div>
    @endif

    <div class="profile-zoom-safe mx-auto max-w-4xl space-y-4">
        <section class="flex flex-col gap-4 rounded-xl border border-[#d88a5e] bg-[#f8eee8] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#c96530] text-white shadow-sm">
                    <i data-lucide="user-round-cog" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Profil PKM</h1>
                    <p class="text-xs text-slate-500">Kelola identitas akun vendor pada dashboard PKM.</p>
                </div>
            </div>
            <span class="w-fit rounded-lg bg-[#c96530] px-3 py-1.5 text-xs font-semibold text-white">Vendor PKM</span>
        </section>

        <section class="grid gap-4 lg:grid-cols-[220px_1fr]">
            <aside class="rounded-xl border border-[#ead4c7] bg-[#fbf7f4] p-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#c96530] text-xl font-bold text-white">
                    {{ $user?->initials() }}
                </div>
                <h2 class="mt-3 font-bold text-slate-900">{{ $user?->name }}</h2>
                <p class="mt-1 break-all text-xs text-slate-500">{{ $user?->email }}</p>
                <div class="mt-4 border-t border-[#ead4c7] pt-4 text-xs text-slate-500">
                    Perubahan nama dan inisial akan langsung tampil pada menu profil dashboard.
                </div>
            </aside>

            <form method="POST" action="{{ route('pkm.profile.update') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required autofocus autocomplete="name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required autocomplete="email"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="nomor_hp" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor HP</label>
                        <input id="nomor_hp" name="nomor_hp" type="text" value="{{ old('nomor_hp', $user?->nomor_hp) }}" autocomplete="tel"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                        @error('nomor_hp') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inisial" class="mb-1.5 block text-xs font-semibold text-slate-700">Inisial</label>
                        <input id="inisial" name="inisial" type="text" value="{{ old('inisial', $user?->inisial) }}" maxlength="20"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm uppercase text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                        @error('inisial') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Hak akses</label>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500">Vendor PKM</div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#c96530] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#ad5228]">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 lg:grid-cols-[220px_1fr]">
            <aside class="rounded-xl border border-[#ead4c7] bg-[#fbf7f4] p-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-800 text-white">
                    <i data-lucide="lock-keyhole" class="h-5 w-5"></i>
                </div>
                <h2 class="mt-3 font-bold text-slate-900">Update Password</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">Masukkan password saat ini sebelum mengganti ke password baru.</p>
            </aside>

            <form method="POST" action="{{ route('pkm.profile.password.update') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="pkm_current_password" class="mb-1.5 block text-xs font-semibold text-slate-700">Password saat ini</label>
                        <input id="pkm_current_password" name="current_password" type="password" required autocomplete="current-password"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                        @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="pkm_password" class="mb-1.5 block text-xs font-semibold text-slate-700">Password baru</label>
                        <input id="pkm_password" name="password" type="password" required autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="pkm_password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-700">Konfirmasi password baru</label>
                        <input id="pkm_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#c96530] focus:ring-2 focus:ring-[#f3d8c8]">
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const success = document.getElementById('profile-success');

            if (success?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Profil diperbarui',
                    text: success.dataset.message,
                    confirmButtonColor: '#c96530',
                });
            }
        });
    </script>
</x-layouts.pkm>
