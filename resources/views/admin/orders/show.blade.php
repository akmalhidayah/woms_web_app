<x-layouts.admin :title="'Lengkapi Dokumen - ' . $order->nomor_order">
    @php
        $abnormalitas = $documentMap->get('abnormalitas');
        $gambarTeknik = $documentMap->get('gambar_teknik');
        $scopeItems = $scopeOfWork?->scope_items ?? [];
        $showSowOnLoad = old('scope_pekerjaan') || old('nama_penginput');
    @endphp

    <div
        x-data="initScopeOfWorkModal({
            showOnLoad: @js((bool) $showSowOnLoad),
            scopeRows: @js($scopeItems ?: [['scope_pekerjaan' => '', 'qty' => '', 'satuan' => '', 'keterangan' => '']]),
            signatureData: @js(old('tanda_tangan', $scopeOfWork?->tanda_tangan ?? '')),
        })"
        class="space-y-6"
    >
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                <div class="font-semibold">Periksa kembali data dokumen order.</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                        <i data-lucide="folder-open" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Lengkapi Dokumen Order</h1>
                        <p class="mt-2 text-sm text-slate-500">{{ $order->nomor_order }} • {{ $order->nama_pekerjaan }}</p>
                    </div>
                </div>

                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Kembali ke Order
                </a>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-3xl border {{ $abnormalitas ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }} p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Abnormalitas</div>
                <div class="mt-2 text-sm {{ $abnormalitas ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $abnormalitas ? 'Dokumen sudah diunggah.' : 'Dokumen belum diunggah.' }}
                </div>
            </article>

            <article class="rounded-3xl border {{ $gambarTeknik ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }} p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Gambar Teknik</div>
                <div class="mt-2 text-sm {{ $gambarTeknik ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $gambarTeknik ? 'Dokumen sudah diunggah.' : 'Dokumen belum diunggah.' }}
                </div>
            </article>

            <article class="rounded-3xl border {{ $scopeOfWork ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50' }} p-5 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Scope of Work</div>
                <div class="mt-2 text-sm {{ $scopeOfWork ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $scopeOfWork ? 'Scope of Work sudah dibuat.' : 'Scope of Work belum dibuat.' }}
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold text-slate-900">Upload Dokumen</h2>
                    <p class="text-sm text-slate-500">Unggah abnormalitas dan gambar teknik dari satu form. Anda bisa mengirim satu file dulu atau dua-duanya sekaligus.</p>
                </div>

                <form method="POST" action="{{ route('admin.orders.documents.store', $order) }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3">
                                <div class="text-sm font-semibold text-slate-900">Dokumen Abnormalitas</div>
                                <div class="mt-1 text-xs text-slate-500">Pilih file jika ingin upload atau mengganti abnormalitas.</div>
                            </div>

                            <input
                                name="abnormalitas_file"
                                type="file"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                            >

                            @if ($abnormalitas)
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $abnormalitas->nama_file_asli }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Upload {{ optional($abnormalitas->uploaded_at)->format('d M Y H:i') }} oleh {{ $abnormalitas->uploader?->name ?? 'Admin' }}
                                    </div>

                                    <div class="mt-4 flex gap-2">
                                        <a href="{{ route('admin.orders.documents.download', [$order, $abnormalitas]) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                            Download
                                        </a>

                                        <form method="POST" action="{{ route('admin.orders.documents.destroy', [$order, $abnormalitas]) }}" onsubmit="return confirm('Hapus dokumen abnormalitas?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-3">
                                <div class="text-sm font-semibold text-slate-900">Dokumen Gambar Teknik</div>
                                <div class="mt-1 text-xs text-slate-500">Pilih file jika ingin upload atau mengganti gambar teknik.</div>
                            </div>

                            <input
                                name="gambar_teknik_file"
                                type="file"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                            >

                            @if ($gambarTeknik)
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $gambarTeknik->nama_file_asli }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Upload {{ optional($gambarTeknik->uploaded_at)->format('d M Y H:i') }} oleh {{ $gambarTeknik->uploader?->name ?? 'Admin' }}
                                    </div>

                                    <div class="mt-4 flex gap-2">
                                        <a href="{{ route('admin.orders.documents.download', [$order, $gambarTeknik]) }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                            Download
                                        </a>

                                        <form method="POST" action="{{ route('admin.orders.documents.destroy', [$order, $gambarTeknik]) }}" onsubmit="return confirm('Hapus dokumen gambar teknik?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-sm text-slate-500">Form ini bisa menyimpan satu dokumen dulu atau dua-duanya sekaligus.</p>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                            Simpan Dokumen
                        </button>
                    </div>
                </form>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold text-slate-900">Scope of Work</h2>
                    <p class="text-sm text-slate-500">Buat dan simpan Scope of Work lengkap dengan tanda tangan pembuat.</p>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    @if ($scopeOfWork)
                        <div class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Nama Penginput</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ $scopeOfWork->nama_penginput }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tanggal Dokumen</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ optional($scopeOfWork->tanggal_dokumen)->format('d M Y') }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tanggal Pemakaian</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ optional($scopeOfWork->tanggal_pemakaian)->format('d M Y') ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Jumlah Item</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ count($scopeItems) }} baris</div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                @foreach ($scopeItems as $item)
                                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $item['scope_pekerjaan'] ?? '-' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Qty {{ $item['qty'] ?? '-' }} • {{ $item['satuan'] ?? '-' }}{{ !empty($item['keterangan']) ? ' • '.$item['keterangan'] : '' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($scopeOfWork->catatan)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    <span class="font-semibold">Catatan:</span> {{ $scopeOfWork->catatan }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-600">
                            Scope of Work belum dibuat untuk order ini.
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex gap-3">
                    @if ($scopeOfWork)
                        <a
                            href="{{ route('admin.orders.scope-of-work.pdf', [$order, $scopeOfWork]) }}"
                            target="_blank"
                            class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Lihat PDF
                        </a>
                    @endif

                    <button
                        type="button"
                        @click="openSowModal()"
                        class="inline-flex flex-1 items-center justify-center rounded-2xl bg-slate-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-900"
                    >
                        {{ $scopeOfWork ? 'Edit Scope of Work' : 'Buat Scope of Work' }}
                    </button>
                </div>
            </article>
        </section>

        @include('admin.orders.partials.scope-of-work-modal', ['order' => $order, 'scopeOfWork' => $scopeOfWork])
    </div>
</x-layouts.admin>
