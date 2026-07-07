<x-layouts.user title="Dokumen Approval - WOMS">
    @php
        $filters = ['' => 'Semua'] + $typeLabels;
        $typeTone = [
            'hpp' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'bast' => 'bg-orange-50 text-orange-700 ring-orange-100',
            'initial_work' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'quality_control' => 'bg-violet-50 text-violet-700 ring-violet-100',
        ];
    @endphp

    <div class="space-y-5">
        <section class="overflow-hidden rounded-[1.5rem] border border-red-100 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-red-100 bg-red-50/70 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full bg-red-800 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-white">
                        <i data-lucide="clipboard-pen-line" class="h-3.5 w-3.5"></i>
                        Approval Inbox
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-tight text-slate-950">Dokumen Approval</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        Daftar dokumen yang menunggu tanda tangan Anda. Tombol buka approval akan melakukan validasi ulang sebelum masuk ke halaman approval bertoken.
                    </p>
                </div>

                <div class="rounded-2xl border border-red-100 bg-white px-4 py-3">
                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Pending TTD</div>
                    <div class="mt-1 text-2xl font-black text-red-800">{{ $totalPending }}</div>
                </div>
            </div>

            <div class="px-5 py-4 sm:px-6">
                <div class="flex flex-wrap gap-2">
                    @foreach ($filters as $filterType => $filterLabel)
                        @php($active = ($selectedType ?? null) === ($filterType !== '' ? $filterType : null))
                        <a
                            href="{{ route('approval-documents.index', $filterType !== '' ? ['type' => $filterType] : []) }}"
                            class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition {{ $active ? 'border-red-800 bg-red-800 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-red-200 hover:bg-red-50 hover:text-red-800' }}"
                        >
                            {{ $filterLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            @if ($documents->isEmpty())
                <div class="px-5 py-16 text-center sm:px-6">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                        <i data-lucide="inbox" class="h-6 w-6"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-black text-slate-950">Belum ada dokumen yang menunggu tanda tangan.</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                        Dokumen hanya muncul ketika step approval aktif menunjuk ke akun Anda dan token approval masih berlaku.
                    </p>
                </div>
            @else
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Dokumen</th>
                                <th class="px-5 py-3">Nomor</th>
                                <th class="px-5 py-3">Pekerjaan</th>
                                <th class="px-5 py-3">Step</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach ($documents as $item)
                                <tr class="align-top">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $typeTone[$item['type']] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                            {{ $item['type_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-slate-950">{{ $item['number'] }}</td>
                                    <td class="max-w-md px-5 py-4">
                                        <div class="font-bold leading-5 text-slate-950">{{ $item['title'] }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">{{ $item['step'] }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ optional($item['submitted_at'])->format('d/m/Y') ?: '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-100">
                                            {{ $item['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a
                                            href="{{ route('approval-documents.open', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-800 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-900"
                                        >
                                            Buka Approval
                                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    @foreach ($documents as $item)
                        <article class="space-y-4 px-5 py-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $typeTone[$item['type']] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                                    {{ $item['type_label'] }}
                                </span>
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 ring-1 ring-amber-100">
                                    {{ $item['status'] }}
                                </span>
                            </div>

                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Nomor</div>
                                <div class="mt-1 text-base font-black text-slate-950">{{ $item['number'] }}</div>
                            </div>

                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Pekerjaan</div>
                                <div class="mt-1 text-sm font-bold leading-5 text-slate-950">{{ $item['title'] }}</div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Step</div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">{{ $item['step'] }}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Tanggal</div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">{{ optional($item['submitted_at'])->format('d/m/Y') ?: '-' }}</div>
                                </div>
                            </div>

                            <a
                                href="{{ route('approval-documents.open', ['type' => $item['type'], 'id' => $item['id']]) }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-800 px-4 py-3 text-sm font-bold text-white transition hover:bg-red-900"
                            >
                                Buka Approval
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.user>
