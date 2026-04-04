<x-layouts.app>
    @php
        $roleLabel = match ($role) {
            'pkm' => 'PKM',
            default => ucfirst($role),
        };
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-900 px-6 py-8 text-white sm:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex w-fit rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                            {{ $roleLabel }}
                        </span>
                        <div class="space-y-2">
                            <h1 class="text-2xl font-semibold sm:text-3xl">{{ $title }}</h1>
                            <p class="max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">{{ $description }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-slate-100">
                        <div class="font-medium">{{ auth()->user()->name }}</div>
                        <div class="text-slate-300">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 px-6 py-6 sm:px-8 lg:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-900">Auth aktif</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Session login, register, redirect role, dan logout sudah siap dipakai sebagai fondasi modul berikutnya.</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-900">Role terpasang</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Akses halaman ini dibatasi middleware `role` sehingga hanya akun {{ $roleLabel }} yang bisa masuk ke dashboard ini.</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-900">Siap dikembangkan</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Placeholder ini bisa langsung diganti dengan widget, statistik, tabel, atau workflow domain WOMS berikutnya.</p>
                </article>
            </div>
        </section>
    </div>
</x-layouts.app>
