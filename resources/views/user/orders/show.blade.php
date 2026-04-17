<x-layouts.user>
    @php
        $timelineToneClasses = [
            'done' => 'border-emerald-200 bg-emerald-50/80',
            'danger' => 'border-red-200 bg-red-50/80',
            'waiting' => 'border-stone-200 bg-stone-50',
        ];

        $timelineBadgeClasses = [
            'done' => 'bg-emerald-100 text-emerald-700',
            'danger' => 'bg-red-100 text-red-700',
            'waiting' => 'bg-stone-200 text-stone-600',
        ];

        $docCard = function (?array $doc, string $title, string $color = 'slate') {
            $colors = [
                'blue' => 'border-sky-200 bg-sky-50/70 text-sky-900',
                'emerald' => 'border-emerald-200 bg-emerald-50/70 text-emerald-900',
                'violet' => 'border-violet-200 bg-violet-50/70 text-violet-900',
                'orange' => 'border-amber-200 bg-amber-50/70 text-amber-900',
                'rose' => 'border-rose-200 bg-rose-50/70 text-rose-900',
                'slate' => 'border-stone-200 bg-stone-50 text-stone-700',
            ];

            $base = $colors[$color] ?? $colors['slate'];

            return [$doc, $title, $base];
        };
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-red-200 hover:text-red-800">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali ke dashboard
            </a>
            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold ring-1 {{ $order['prioritas_badge_classes'] }}">
                {{ $order['prioritas_label'] }}
            </span>
        </div>

        <section class="overflow-hidden rounded-[24px] border border-stone-200 bg-white shadow-sm">
            <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="border-b border-stone-200 bg-[linear-gradient(180deg,#fff_0%,#fafaf9_100%)] p-5 sm:p-6 lg:border-b-0 lg:border-r">
                    <div class="space-y-4">
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-red-800 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-white">
                            Tracking Order
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-slate-500">Nomor Order {{ $order['nomor_order'] }}</div>
                            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900">{{ $order['nama_pekerjaan'] }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $order['deskripsi'] ?: 'Deskripsi order belum ditambahkan.' }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Notifikasi</div>
                                <div class="mt-2 text-lg font-bold text-slate-900">{{ $order['notifikasi'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Tanggal Order</div>
                                <div class="mt-2 text-lg font-bold text-slate-900">{{ $order['tanggal_order'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Unit Kerja</div>
                                <div class="mt-2 text-base font-bold text-slate-900">{{ $order['unit_kerja'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Seksi</div>
                                <div class="mt-2 text-base font-bold text-slate-900">{{ $order['seksi'] ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-stone-50/70 p-5 sm:p-6">
                    <div class="rounded-[22px] border border-red-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Progress Pekerjaan</div>
                                <div class="mt-2 text-sm text-slate-600">{{ $order['progress']['source'] }}</div>
                            </div>
                            <div class="text-3xl font-black text-slate-900">{{ $order['progress']['percent'] }}%</div>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-stone-200">
                            <div class="h-full rounded-full bg-red-700" style="width: {{ max(0, min(100, (int) $order['progress']['percent'])) }}%"></div>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Target Selesai</div>
                                <div class="mt-2 text-sm font-semibold text-slate-900">{{ $order['progress']['target'] ?: $order['target_selesai_order'] ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Approval Awal</div>
                                <div class="mt-2 text-sm font-semibold text-slate-900">{{ $order['approval_label'] }}</div>
                            </div>
                        </div>
                        @if ($order['approval_note'])
                            <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Catatan</div>
                                <div class="mt-2 text-sm leading-6 text-slate-700">{{ $order['approval_note'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
            <div>
                <h2 class="text-xl font-black text-slate-900">Timeline Proses</h2>
                <p class="mt-1 text-sm text-slate-500">Semua tahapan order diringkas dalam satu alur yang mudah dipantau.</p>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($order['timeline'] as $item)
                    <article class="rounded-2xl border p-4 {{ $timelineToneClasses[$item['tone']] ?? $timelineToneClasses['waiting'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">{{ $item['label'] }}</div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold {{ $timelineBadgeClasses[$item['tone']] ?? $timelineBadgeClasses['waiting'] }}">
                                {{ $item['tone'] === 'done' ? 'Selesai' : ($item['tone'] === 'danger' ? 'Perhatian' : 'Pending') }}
                            </span>
                        </div>
                        <div class="mt-3 text-base font-bold leading-6 text-slate-900">{{ $item['value'] }}</div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-xl font-black text-slate-900">Pusat Dokumen</h2>
                <p class="mt-1 text-sm text-slate-500">Semua dokumen penting order ini bisa dibuka langsung dari sini.</p>

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach ([
                        $docCard($order['documents']['abnormalitas'], 'Abnormalitas', 'rose'),
                        $docCard($order['documents']['gambar_teknik'], 'Gambar Teknik', 'blue'),
                        $docCard($order['documents']['scope_of_work'] ? ['label' => 'Scope of Work', 'url' => $order['documents']['scope_of_work']] : null, 'Scope of Work', 'emerald'),
                        $docCard($order['documents']['initial_work'] ? ['label' => 'Initial Work', 'url' => $order['documents']['initial_work']] : null, 'Initial Work', 'violet'),
                        $docCard($order['documents']['hpp'] ? ['label' => 'HPP PDF', 'url' => $order['documents']['hpp']] : null, 'HPP PDF', 'blue'),
                        $docCard($order['documents']['purchase_order'] ? ['label' => 'Dokumen PO', 'url' => $order['documents']['purchase_order']] : null, 'Dokumen PO', 'emerald'),
                        $docCard($order['documents']['bast_termin_1'] ? ['label' => 'BAST Termin 1', 'url' => $order['documents']['bast_termin_1']] : null, 'BAST Termin 1', 'orange'),
                        $docCard($order['documents']['bast_termin_2'] ? ['label' => 'BAST Termin 2', 'url' => $order['documents']['bast_termin_2']] : null, 'BAST Termin 2', 'orange'),
                    ] as [$doc, $title, $classes])
                        <article class="rounded-2xl border p-4 {{ $classes }}">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] opacity-70">{{ $title }}</div>
                            @if ($doc)
                                <div class="mt-3 text-base font-bold">{{ $doc['label'] }}</div>
                                <a href="{{ $doc['url'] }}" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-current/20 bg-white/90 px-4 py-2 text-sm font-bold transition hover:bg-white">
                                    <i data-lucide="file-text" class="h-4 w-4"></i>
                                    Buka Dokumen
                                </a>
                            @else
                                <div class="mt-3 inline-flex rounded-full bg-white/70 px-3 py-1 text-xs font-semibold opacity-90">Belum tersedia</div>
                            @endif
                        </article>
                    @endforeach
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    @foreach ([
                        ['title' => 'LPJ Termin 1', 'doc' => $order['documents']['lpj_termin_1']],
                        ['title' => 'PPL Termin 1', 'doc' => $order['documents']['ppl_termin_1']],
                        ['title' => 'LPJ Termin 2', 'doc' => $order['documents']['lpj_termin_2']],
                        ['title' => 'PPL Termin 2', 'doc' => $order['documents']['ppl_termin_2']],
                    ] as $docRow)
                        <article class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-slate-700">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">{{ $docRow['title'] }}</div>
                            @if ($docRow['doc'])
                                <div class="mt-3 text-base font-bold text-slate-900">{{ $docRow['doc']['label'] }}</div>
                                <a href="{{ $docRow['doc']['url'] }}" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-red-200 hover:text-red-800">
                                    <i data-lucide="file-badge-2" class="h-4 w-4"></i>
                                    Lihat File
                                </a>
                            @else
                                <div class="mt-3 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">Belum tersedia</div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5">
                <section class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xl font-black text-slate-900">Ringkasan HPP & Anggaran</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl border border-red-100 bg-red-50/40 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Status HPP</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $order['hpp']['status'] }}</div>
                            <div class="mt-1 text-sm text-slate-600">
                                {{ $order['hpp']['total'] !== null ? 'Rp '.number_format((float) $order['hpp']['total'], 2, ',', '.') : 'Nilai belum tersedia' }}
                            </div>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Verifikasi Anggaran</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $order['budget']['status'] }}</div>
                            <div class="mt-3 space-y-1 text-sm text-slate-600">
                                <div>Kategori item: {{ $order['budget']['kategori_item'] ?: '-' }}</div>
                                <div>Kategori biaya: {{ $order['budget']['kategori_biaya'] ?: '-' }}</div>
                                <div>Cost element: {{ $order['budget']['cost_element'] ?: '-' }}</div>
                            </div>
                            @if ($order['budget']['catatan'])
                                <div class="mt-3 rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                                    {{ $order['budget']['catatan'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-[22px] border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-xl font-black text-slate-900">PO & Garansi</h2>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50/40 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Nomor PO</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $order['purchase_order']['number'] ?: '-' }}</div>
                            <div class="mt-1 text-sm text-slate-600">Target selesai: {{ $order['purchase_order']['target'] ?: '-' }}</div>
                            @if ($order['purchase_order']['admin_note'])
                                <div class="mt-3 rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700">
                                    {{ $order['purchase_order']['admin_note'] }}
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-700">Garansi</div>
                            @if ($order['garansi'])
                                <div class="mt-2 text-base font-bold text-slate-900">{{ $order['garansi']['months'] }} bulan</div>
                                <div class="mt-1 text-sm text-slate-600">Mulai {{ $order['garansi']['start'] ?: '-' }} • Berakhir {{ $order['garansi']['end'] ?: '-' }}</div>
                            @else
                                <div class="mt-2 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">Data garansi belum tersedia</div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-layouts.user>
