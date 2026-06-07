<x-layouts.admin :title="'Lengkapi Dokumen - ' . $order->nomor_order">
    @php
        $abnormalitas = $documentMap->get('abnormalitas');
        $gambarTeknik = $documentMap->get('gambar_teknik');
        $scopeItems = $scopeOfWork?->scope_items ?? [];
        $showSowOnLoad = old('scope_pekerjaan') || old('nama_penginput');
        $abnormalitasUploadHint = 'Maks. 10 MB - PDF, DOC, DOCX';
        $gambarTeknikUploadHint = 'Maks. 10 MB - JPG, JPEG, PDF, DOC, DOCX';
    @endphp

    <div
        x-data="initScopeOfWorkModal({
            showOnLoad: @js((bool) $showSowOnLoad),
            scopeRows: @js($scopeItems ?: [['scope_pekerjaan' => '', 'qty' => '', 'satuan' => '', 'keterangan' => '']]),
            signatureData: '',
        })"
        class="space-y-3"
    >
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] text-rose-700">
                <div class="font-semibold">Periksa kembali data dokumen.</div>
                <ul class="mt-1 list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <i data-lucide="folder-open" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-[15px] font-bold tracking-tight text-slate-900">Lengkapi Dokumen</h1>
                        <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $order->nomor_order }} - {{ $order->nama_pekerjaan }}</p>
                    </div>
                </div>

                <a href="{{ route('admin.orders.index') }}" class="inline-flex w-fit items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100">
                    <i data-lucide="arrow-left" class="h-3 w-3"></i>
                    Kembali
                </a>
            </div>

            <div class="mt-2 flex flex-wrap gap-1.5 border-t border-slate-100 pt-2">
                @foreach ([
                    ['label' => 'Abnormalitas', 'ready' => (bool) $abnormalitas],
                    ['label' => 'Gambar Teknik', 'ready' => (bool) $gambarTeknik],
                    ['label' => 'Scope of Work', 'ready' => (bool) $scopeOfWork],
                ] as $statusItem)
                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[9px] font-semibold {{ $statusItem['ready'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                        <i data-lucide="{{ $statusItem['ready'] ? 'check' : 'minus' }}" class="h-2.5 w-2.5"></i>
                        {{ $statusItem['label'] }}
                    </span>
                @endforeach
            </div>
        </section>

        <section class="grid gap-3 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-[13px] font-bold text-slate-900">Upload Dokumen</h2>

                <form method="POST" action="{{ route('admin.orders.documents.store', $order) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                    @csrf

                    <div class="grid gap-3 lg:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[10px] font-semibold text-slate-700">Abnormalitas</label>
                            <input
                                name="abnormalitas_file"
                                type="file"
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[9px] text-slate-700 file:mr-2 file:rounded-md file:border-0 file:bg-slate-700 file:px-2.5 file:py-1 file:text-[9px] file:font-semibold file:text-white hover:file:bg-slate-800"
                            >
                            <div class="mt-1 text-[8px] text-slate-400">{{ $abnormalitasUploadHint }}</div>

                            @if ($abnormalitas)
                                <div class="mt-1.5 flex items-center justify-between gap-2 rounded-lg bg-emerald-50 px-2 py-1.5 text-[9px]">
                                    <span class="min-w-0 truncate font-medium text-emerald-800" title="{{ $abnormalitas->nama_file_asli }}">{{ $abnormalitas->nama_file_asli }}</span>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <a href="{{ route('admin.orders.documents.download', [$order, $abnormalitas]) }}" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-emerald-200 bg-white text-emerald-700" title="Download">
                                            <i data-lucide="download" class="h-3 w-3"></i>
                                        </a>
                                        <button type="submit" form="delete-abnormalitas-form" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-700" title="Hapus">
                                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="mb-1 block text-[10px] font-semibold text-slate-700">Gambar Teknik</label>
                            <input
                                name="gambar_teknik_file"
                                type="file"
                                accept=".jpg,.jpeg,.pdf,.doc,.docx,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[9px] text-slate-700 file:mr-2 file:rounded-md file:border-0 file:bg-slate-700 file:px-2.5 file:py-1 file:text-[9px] file:font-semibold file:text-white hover:file:bg-slate-800"
                            >
                            <div class="mt-1 text-[8px] text-slate-400">{{ $gambarTeknikUploadHint }}</div>

                            @if ($gambarTeknik)
                                <div class="mt-1.5 flex items-center justify-between gap-2 rounded-lg bg-emerald-50 px-2 py-1.5 text-[9px]">
                                    <span class="min-w-0 truncate font-medium text-emerald-800" title="{{ $gambarTeknik->nama_file_asli }}">{{ $gambarTeknik->nama_file_asli }}</span>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <a href="{{ route('admin.orders.documents.download', [$order, $gambarTeknik]) }}" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-emerald-200 bg-white text-emerald-700" title="Download">
                                            <i data-lucide="download" class="h-3 w-3"></i>
                                        </a>
                                        <button type="submit" form="delete-gambar-teknik-form" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-700" title="Hapus">
                                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-3 py-2 text-[10px] font-semibold text-white transition hover:bg-slate-900">
                            Simpan Dokumen
                        </button>
                    </div>
                </form>

                @if ($abnormalitas)
                    <form id="delete-abnormalitas-form" method="POST" action="{{ route('admin.orders.documents.destroy', [$order, $abnormalitas]) }}" onsubmit="return confirm('Hapus dokumen abnormalitas?')" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif

                @if ($gambarTeknik)
                    <form id="delete-gambar-teknik-form" method="POST" action="{{ route('admin.orders.documents.destroy', [$order, $gambarTeknik]) }}" onsubmit="return confirm('Hapus dokumen gambar teknik?')" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-[13px] font-bold text-slate-900">Scope of Work</h2>

                <div class="mt-3">
                    @if ($scopeOfWork)
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-x-3 gap-y-1 rounded-lg bg-slate-50 px-2.5 py-2 text-[9px] text-slate-600">
                                <strong class="text-slate-800">{{ $scopeOfWork->nama_penginput }}</strong>
                                <span>{{ optional($scopeOfWork->tanggal_dokumen)->format('d M Y') }}</span>
                                <span>{{ count($scopeItems) }} item</span>
                            </div>

                            <div class="space-y-1">
                                @foreach ($scopeItems as $item)
                                    <div class="rounded-lg border border-slate-200 px-2.5 py-2">
                                        <div class="text-[10px] font-semibold text-slate-800">{{ $item['scope_pekerjaan'] ?? '-' }}</div>
                                        <div class="mt-0.5 text-[8px] text-slate-500">
                                            {{ $item['qty'] ?? '-' }} {{ $item['satuan'] ?? '-' }}{{ ! empty($item['keterangan']) ? ' - '.$item['keterangan'] : '' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($scopeOfWork->catatan)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-[9px] text-amber-800">
                                    {{ $scopeOfWork->catatan }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-center text-[10px] text-slate-500">
                            Belum dibuat.
                        </div>
                    @endif
                </div>

                <div class="mt-3 flex gap-2">
                    @if ($scopeOfWork)
                        <a
                            href="{{ route('admin.orders.scope-of-work.pdf', [$order, $scopeOfWork]) }}"
                            target="_blank"
                            class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            PDF
                        </a>
                    @endif

                    <button
                        type="button"
                        @click="openSowModal()"
                        class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-800 px-3 py-2 text-[10px] font-semibold text-white transition hover:bg-slate-900"
                    >
                        {{ $scopeOfWork ? 'Edit' : 'Buat Scope of Work' }}
                    </button>
                </div>
            </article>
        </section>

        @include('admin.orders.partials.scope-of-work-modal', ['order' => $order, 'scopeOfWork' => $scopeOfWork])
    </div>
</x-layouts.admin>
