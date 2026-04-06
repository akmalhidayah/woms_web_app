<x-layouts.admin title="Kuota Anggaran & OA">
    @php
        $currency = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    @endphp

    @if (session('success'))
        <div id="outline-agreement-success" data-message="{{ session('success') }}" class="hidden"></div>
    @endif

    <div class="space-y-6" x-data="{ createOpen: {{ ($errors->any() && old('_method') !== 'PUT') ? 'true' : 'false' }} }">
        <section class="rounded-[1.75rem] border border-emerald-100 px-6 py-6 shadow-sm" style="background: linear-gradient(135deg, #effdf4 0%, #f8fffb 45%, #eef8ff 100%);">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-200">
                        <i data-lucide="file-text" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h1 class="text-[2rem] font-bold leading-none tracking-tight text-slate-900">Kuota Anggaran &amp; OA</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">
                            Kelola master Outline Agreement, histori adendum, dan target biaya pemeliharaan per tahun secara aman tanpa merusak histori kontrak lama.
                        </p>
                    </div>
                </div>

                <button type="button" @click="createOpen = true" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <i data-lucide="plus-circle" class="h-4 w-4"></i>
                    Buat OA Baru
                </button>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">OA Aktif</p>
                <div class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['active_count'] }}</div>
                <p class="mt-2 text-sm text-slate-500">Master OA yang sedang berjalan saat ini.</p>
            </article>

            <article class="rounded-[1.5rem] border border-amber-200 bg-amber-50/70 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-500">Mendekati Habis</p>
                <div class="mt-3 text-3xl font-bold text-amber-700">{{ $summary['expiring_count'] }}</div>
                <p class="mt-2 text-sm text-amber-700/80">OA aktif dengan masa berlaku kurang dari 60 hari.</p>
            </article>

            <article class="rounded-[1.5rem] border border-rose-200 bg-rose-50/70 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-rose-500">Expired</p>
                <div class="mt-3 text-3xl font-bold text-rose-700">{{ $summary['expired_count'] }}</div>
                <p class="mt-2 text-sm text-rose-700/80">Tetap tersimpan aman untuk histori kontrak lama.</p>
            </article>

            <article class="rounded-[1.5rem] border border-sky-200 bg-sky-50/70 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-500">Nilai Aktif</p>
                <div class="mt-3 text-2xl font-bold text-sky-800">{{ $currency($summary['active_total']) }}</div>
                <p class="mt-2 text-sm text-sky-700/80">Akumulasi nilai aktif seluruh OA master saat ini.</p>
            </article>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.outline-agreements.index') }}" class="grid gap-3 md:grid-cols-[1.2fr_220px_auto_auto] md:items-end">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pencarian</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor OA, unit kerja, atau nama kontrak..." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($status === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>

                <a href="{{ route('admin.outline-agreements.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Reset
                </a>
            </form>
        </section>

        <section class="space-y-5">
            @forelse ($agreements as $agreement)
                <details class="group rounded-[1.5rem] border border-slate-200 bg-white shadow-sm" @if($loop->first) open @endif>
                    <summary class="cursor-pointer list-none px-5 py-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                        {{ $agreement->nomor_oa }}
                                    </span>
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold ring-1 {{ $agreement->statusBadgeClasses() }}">
                                        {{ \App\Models\OutlineAgreement::statusOptions()[$agreement->status] ?? ucfirst($agreement->status) }}
                                    </span>
                                    @if ($agreement->isExpiringSoon())
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">
                                            Segera berakhir
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $agreement->nama_kontrak }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $agreement->jenis_kontrak }} · {{ $agreement->unitWork->name ?? '-' }}
                                        @if ($agreement->unitWork?->department)
                                            · {{ $agreement->unitWork->department->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[25rem] xl:max-w-[27rem]">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Nilai Aktif</div>
                                    <div class="mt-2 text-lg font-bold text-slate-900">{{ $currency($agreement->current_total_nilai) }}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Periode Aktif</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ $agreement->periodeAktifLabel() }}</div>
                                </div>
                            </div>
                        </div>
                    </summary>

                    <div class="border-t border-slate-200 px-5 py-5">
                        <div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                            <div class="space-y-5">
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/70 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-800">Ringkasan Master OA</h3>
                                            <p class="mt-1 text-xs text-slate-500">Snapshot aktif dipakai untuk dashboard dan proses kontrak berjalan.</p>
                                        </div>

                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            data-edit-trigger
                                            data-id="{{ $agreement->id }}"
                                            data-nomor="{{ $agreement->nomor_oa }}"
                                            data-unit-work-id="{{ $agreement->unit_work_id }}"
                                            data-jenis="{{ $agreement->jenis_kontrak }}"
                                            data-nama="{{ $agreement->nama_kontrak }}"
                                            data-total="{{ (float) $agreement->current_total_nilai }}"
                                            data-period-start="{{ optional($agreement->current_period_start)->format('Y-m-d') }}"
                                            data-period-end="{{ optional($agreement->current_period_end)->format('Y-m-d') }}"
                                            data-initial-value="{{ (float) $agreement->nilai_kontrak_awal }}"
                                            data-targets='@json($agreement->yearlyTargets->map(fn ($target) => ["year" => $target->tahun, "value" => (float) $target->nilai_target])->values())'
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                            Edit OA
                                        </button>
                                    </div>

                                    <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl border border-white bg-white px-4 py-3">
                                            <dt class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">Nilai Awal</dt>
                                            <dd class="mt-2 text-base font-bold text-slate-900">{{ $currency($agreement->nilai_kontrak_awal) }}</dd>
                                        </div>
                                        <div class="rounded-xl border border-white bg-white px-4 py-3">
                                            <dt class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">Tambahan Nilai</dt>
                                            <dd class="mt-2 text-base font-bold text-slate-900">{{ $currency($agreement->totalTambahanValue()) }}</dd>
                                        </div>
                                        <div class="rounded-xl border border-white bg-white px-4 py-3">
                                            <dt class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">Periode Awal</dt>
                                            <dd class="mt-2 text-sm font-semibold text-slate-900">
                                                {{ optional($agreement->periode_awal_start)->format('d M Y') }} - {{ optional($agreement->periode_awal_end)->format('d M Y') }}
                                            </dd>
                                        </div>
                                        <div class="rounded-xl border border-white bg-white px-4 py-3">
                                            <dt class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">Jumlah Histori</dt>
                                            <dd class="mt-2 text-base font-bold text-slate-900">{{ $agreement->histories->count() }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-800">Target Biaya Pemeliharaan</h3>
                                            <p class="mt-1 text-xs text-slate-500">Target tahunan terpisah dari kuota kontrak aktif.</p>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-400">{{ $agreement->yearlyTargets->count() }} target</span>
                                    </div>

                                    <div class="mt-4 space-y-2">
                                        @forelse ($agreement->yearlyTargets as $target)
                                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <span class="text-sm font-medium text-slate-700">{{ $target->tahun }}</span>
                                                <span class="text-sm font-semibold text-slate-900">{{ $currency($target->nilai_target) }}</span>
                                            </div>
                                        @empty
                                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">
                                                Belum ada target biaya tahunan untuk OA ini.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[1.25rem] border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800">Histori OA / Adendum</h3>
                                        <p class="mt-1 text-xs text-slate-500">Semua perubahan tersimpan append-only agar histori kontrak tetap aman.</p>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-400">Rev. {{ $agreement->histories->max('revision_no') }}</span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach ($agreement->histories as $history)
                                        <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                                            {{ $history->typeLabel() }}
                                                        </span>
                                                        <span class="text-[11px] font-semibold text-slate-400">REV {{ $history->revision_no }}</span>
                                                    </div>
                                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $currency($history->snapshot_total_nilai) }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        {{ optional($history->snapshot_period_start)->format('d M Y') }} - {{ optional($history->snapshot_period_end)->format('d M Y') }}
                                                    </p>
                                                </div>
                                                <div class="text-right text-xs text-slate-400">
                                                    <div>{{ optional($history->created_at)->format('d M Y') }}</div>
                                                    <div class="mt-1">{{ $history->creator->name ?? '-' }}</div>
                                                </div>
                                            </div>

                                            @if ($history->nilai_tambahan > 0)
                                                <div class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    Tambahan: {{ $currency($history->nilai_tambahan) }}
                                                </div>
                                            @endif

                                            @if ($history->keterangan)
                                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $history->keterangan }}</p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </details>
            @empty
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <i data-lucide="file-stack" class="h-6 w-6"></i>
                    </div>
                    <h3 class="mt-5 text-xl font-semibold text-slate-900">Belum ada Outline Agreement</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                        Mulai dengan membuat OA baru, lalu semua extend, tambahan nilai, dan revisi akan tersimpan sebagai histori adendum.
                    </p>
                    <button type="button" @click="createOpen = true" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <i data-lucide="plus-circle" class="h-4 w-4"></i>
                        Buat OA Baru
                    </button>
                </div>
            @endforelse

            @if ($agreements->hasPages())
                <div class="pt-2">
                    {{ $agreements->links() }}
                </div>
            @endif
        </section>

        @include('admin.outline-agreements.partials.create-modal')
        @include('admin.outline-agreements.partials.edit-modal')
    </div>

    @include('admin.outline-agreements.partials.scripts')
</x-layouts.admin>
