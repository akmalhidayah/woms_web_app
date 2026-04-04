<x-layouts.pkm :title="$pageTitle . ' - PKM'">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-orange-200 bg-gradient-to-r from-orange-500 via-orange-500 to-amber-500 text-white">
            <div class="grid gap-6 px-6 py-8 lg:grid-cols-[1.35fr_0.8fr] lg:px-8">
                <div class="space-y-4">
                    <span class="inline-flex w-fit rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                        PKM Dashboard
                    </span>
                    <div class="space-y-3">
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $pageTitle }}</h1>
                        <p class="max-w-2xl text-sm leading-7 text-orange-50 sm:text-base">{{ $pageDescription }}</p>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-sm">
                    <div class="text-sm font-semibold text-white">Vendor aktif</div>
                    <div class="mt-3 space-y-2 text-sm text-orange-50">
                        <div>{{ auth()->user()->name }}</div>
                        <div>{{ auth()->user()->email }}</div>
                        <div class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em]">
                            {{ auth()->user()->role }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-orange-50 p-5">
                <div class="text-sm font-semibold text-orange-700">Pekerjaan Aktif</div>
                <div class="mt-3 text-3xl font-bold text-slate-900">18</div>
                <p class="mt-2 text-sm leading-6 text-slate-600">Placeholder ringkasan pekerjaan vendor yang sedang berjalan.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-orange-50 p-5">
                <div class="text-sm font-semibold text-orange-700">Waiting List</div>
                <div class="mt-3 text-3xl font-bold text-slate-900">6</div>
                <p class="mt-2 text-sm leading-6 text-slate-600">Area ini nanti bisa menampilkan pekerjaan yang masih menunggu proses.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-orange-50 p-5">
                <div class="text-sm font-semibold text-orange-700">Dokumen</div>
                <div class="mt-3 text-3xl font-bold text-slate-900">11</div>
                <p class="mt-2 text-sm leading-6 text-slate-600">Placeholder jumlah dokumen, LHPP, dan lampiran vendor.</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-orange-50 p-5">
                <div class="text-sm font-semibold text-orange-700">Item Kebutuhan</div>
                <div class="mt-3 text-3xl font-bold text-slate-900">27</div>
                <p class="mt-2 text-sm leading-6 text-slate-600">Nanti bisa diisi item kebutuhan material dan komponen kerja.</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-3xl border border-slate-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Navigasi PKM</h2>
                        <p class="mt-1 text-sm text-slate-500">Semua menu sidebar sudah dibuat agar flow frontend PKM terasa utuh.</p>
                    </div>
                    <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-orange-700">Frontend Only</span>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'Dashboard',
                        'List Pekerjaan',
                        'Item Kebutuhan',
                        'Buat LHPP',
                        'Dokumen',
                        'Profil Vendor',
                    ] as $item)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-medium text-slate-700">
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">Status Halaman</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                        <div class="text-sm font-semibold text-emerald-800">Selesai</div>
                        <p class="mt-1 text-sm leading-6 text-emerald-700">Tema oranye, sidebar vendor, topbar, dan struktur konten utama sudah mengikuti referensi PKM.</p>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                        <div class="text-sm font-semibold text-amber-800">Placeholder</div>
                        <p class="mt-1 text-sm leading-6 text-amber-700">Semua angka, status, dan isi kartu masih dummy untuk kebutuhan frontend awal.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-sm font-semibold text-slate-800">Next step</div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Jika visualnya sudah cocok, menu PKM ini bisa kita pecah menjadi halaman nyata satu per satu.</p>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-layouts.pkm>
