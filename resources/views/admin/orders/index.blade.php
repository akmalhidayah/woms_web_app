<x-layouts.admin title="Order">
    @php
        $today = now()->format('Y-m-d');
    @endphp

    @if (session('status'))
        <div id="flash-success" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @if ($errors->any())
        <div id="flash-error" data-message="{{ implode(' • ', $errors->all()) }}" class="hidden"></div>
    @endif

    <div class="space-y-6">
        <div class="space-y-5">
            <section
                class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm"
                style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                            <i data-lucide="inbox" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">Order</h1>
                            <p class="mt-1.5 text-[13px] text-slate-500">Pantau order pekerjaan dan kawat las dengan filter cepat.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        id="openCreateOrderModal"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-600"
                    >
                        <i data-lucide="rocket" class="h-[13px] w-[13px]"></i>
                        Buat Order
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                            <div class="grid flex-1 gap-2.5 md:grid-cols-3 xl:grid-cols-[1.15fr_1fr_1fr]">
                                <div class="flex flex-col">
                                    <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                                    <input
                                        id="search"
                                        name="search"
                                        type="text"
                                        value="{{ $search }}"
                                        placeholder="Cari nomor order atau pekerjaan..."
                                        class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
                                    >
                                </div>

                                <div class="flex flex-col">
                                    <label for="seksi" class="mb-1.5 text-[10px] font-semibold text-slate-700">Regu</label>
                                    <select
                                        id="seksi"
                                        name="seksi"
                                        class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none"
                                    >
                                        <option value="">-- Semua --</option>
                                        @foreach ($seksiOptions as $option)
                                            <option value="{{ $option }}" @selected($selectedSeksi === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col">
                                    <label for="catatan_status" class="mb-1.5 text-[10px] font-semibold text-slate-700">Status Catatan</label>
                                    <select
                                        id="catatan_status"
                                        name="catatan_status"
                                        class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none"
                                    >
                                        <option value="">-- Semua --</option>
                                        @foreach ($userNoteStatusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($selectedCatatanStatus === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                                    <i data-lucide="filter" class="h-[13px] w-[13px]"></i>
                                </button>

                                <a href="{{ route('admin.orders.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                                    <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-[13px] text-slate-700">
                            <thead class="bg-slate-200/80 text-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase">Nomor Order</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase">Detail Pekerjaan</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase">Status & Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse ($orders as $index => $order)
                                    @php
                                        $documentByType = $order->documents->keyBy(fn ($document) => $document->jenis_dokumen->value);
                                        $abnormalDocument = $documentByType->get('abnormalitas');
                                        $gambarDocument = $documentByType->get('gambar_teknik');
                                        $hasAbnormal = $abnormalDocument !== null;
                                        $hasGambar = $gambarDocument !== null;
                                        $hasScope = $order->scopeOfWork !== null;
                                        $currentNoteStatus = $order->catatan_status?->value ?? 'pending';
                                        $noteDetailOptions = $userNoteDetailOptions[$currentNoteStatus] ?? [];
                                        $noteStatusClasses = match ($currentNoteStatus) {
                                            'approved_jasa' => 'bg-amber-100 text-amber-700',
                                            'approved_workshop' => 'bg-blue-100 text-blue-700',
                                            'approved_workshop_jasa' => 'bg-emerald-100 text-emerald-700',
                                            'rejected' => 'bg-rose-100 text-rose-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                        $priorityGroup = \App\Models\Order::priorityPrimaryFor($order->prioritas);
                                        $priorityLabel = match ($priorityGroup) {
                                            'emergency' => 'Emergency',
                                            'high' => 'High',
                                            default => 'Medium',
                                        };
                                        $priorityTextClasses = match ($priorityGroup) {
                                            'emergency' => 'text-rose-600',
                                            'high' => 'text-amber-600',
                                            default => 'text-emerald-600',
                                        };
                                        $canManageInitialWork = $priorityGroup === 'emergency' || $order->initialWork !== null;
                                        $documentIndicators = [
                                            [
                                                'label' => 'Abnormalitas',
                                                'available' => $hasAbnormal,
                                                'icon' => 'file',
                                                'url' => $hasAbnormal ? route('admin.orders.documents.preview', [$order, $abnormalDocument]) : null,
                                                'classes' => $hasAbnormal
                                                    ? 'border-red-200 bg-red-50 text-red-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                            [
                                                'label' => 'Gambar Teknik',
                                                'available' => $hasGambar,
                                                'icon' => 'image',
                                                'url' => $hasGambar ? route('admin.orders.documents.preview', [$order, $gambarDocument]) : null,
                                                'classes' => $hasGambar
                                                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                            [
                                                'label' => 'Scope of Work',
                                                'available' => $hasScope,
                                                'icon' => 'file-text',
                                                'url' => $hasScope ? route('admin.orders.scope-of-work.pdf', [$order, $order->scopeOfWork]) : null,
                                                'classes' => $hasScope
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : 'border-slate-200 bg-slate-50 text-slate-500',
                                            ],
                                        ];
                                    @endphp

                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60' }} align-top transition hover:bg-slate-50">
                                        <td class="px-3 py-3 text-[11px] font-semibold text-slate-600">
                                            <div class="min-w-[165px] rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm">
                                                <div class="text-[13px] font-bold tracking-[0.01em] text-slate-900">{{ $order->nomor_order }}</div>
                                                @if ($order->notifikasi)
                                                    <div class="mt-1 text-[11px] font-medium text-blue-600">Notif: {{ $order->notifikasi }}</div>
                                                @endif
                                                <div class="mt-2 border-t border-slate-100 pt-2">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Tanggal Order</div>
                                                    <div class="mt-1 text-[11px] font-semibold text-slate-700">{{ $order->tanggal_order->format('Y-m-d') }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-3 py-3">
                                            <div class="grid gap-2.5">
                                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                                                    <div class="text-[14px] font-semibold leading-snug text-slate-900">{{ $order->nama_pekerjaan }}</div>
                                                    <div class="mt-2 grid gap-2 md:grid-cols-2">
                                                        <div>
                                                            <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Unit Kerja</div>
                                                            <div class="mt-1 text-[12px] font-medium leading-5 text-slate-700">{{ $order->unit_kerja }}</div>
                                                        </div>

                                                        <div>
                                                            <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-blue-400">Seksi</div>
                                                            <div class="mt-1 text-[12px] font-medium leading-5 text-blue-700">{{ $order->seksi }}</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                                    @foreach ($documentIndicators as $indicator)
                                                        @if ($indicator['available'] && $indicator['url'])
                                                            <a
                                                                href="{{ $indicator['url'] }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-[12px] font-semibold transition hover:-translate-y-0.5 hover:shadow-sm {{ $indicator['classes'] }}"
                                                                title="Buka {{ $indicator['label'] }}"
                                                            >
                                                                <i data-lucide="{{ $indicator['icon'] }}" class="h-3 w-3"></i>
                                                                <span>{{ $indicator['label'] }}</span>
                                                                <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold">
                                                                    Ada
                                                                </span>
                                                            </a>
                                                        @else
                                                            <span class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-[12px] font-semibold {{ $indicator['classes'] }}">
                                                                <i data-lucide="{{ $indicator['icon'] }}" class="h-3 w-3"></i>
                                                                <span>{{ $indicator['label'] }}</span>
                                                                <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold">
                                                                    Belum
                                                                </span>
                                                            </span>
                                                        @endif
                                                    @endforeach

                                                    <a
                                                        href="{{ route('admin.orders.show', $order) }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                        title="Edit Dokumen"
                                                    >
                                                        <i data-lucide="folder-open" class="h-3 w-3"></i>
                                                    </a>

                                                    @if ($canManageInitialWork)
                                                        @if ($order->initialWork)
                                                            <a
                                                                href="{{ route('admin.orders.initial-work.pdf', [$order, $order->initialWork]) }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="inline-flex items-center gap-1.5 rounded-xl border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[12px] font-semibold text-orange-700 transition hover:-translate-y-0.5 hover:shadow-sm"
                                                                title="Lihat Initial Work PDF"
                                                            >
                                                                <i data-lucide="file-text" class="h-3 w-3"></i>
                                                                <span>Initial Work PDF</span>
                                                            </a>

                                                            <button
                                                                type="button"
                                                                class="edit-initial-work-trigger inline-flex h-8 w-8 items-center justify-center rounded-xl border border-orange-200 bg-white text-orange-700 transition hover:border-orange-300 hover:bg-orange-50"
                                                                data-mode="edit"
                                                                data-action="{{ route('admin.orders.initial-work.update', [$order, $order->initialWork]) }}"
                                                                data-order-key="{{ $order->getRouteKey() }}"
                                                                data-nomor-order="{{ $order->nomor_order }}"
                                                                data-notifikasi="{{ $order->notifikasi }}"
                                                                data-unit-kerja="{{ $order->unit_kerja }}"
                                                                data-seksi="{{ $order->seksi }}"
                                                                data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                                data-document-number="{{ $order->initialWork->nomor_initial_work }}"
                                                                data-kepada-yth="{{ $order->initialWork->kepada_yth }}"
                                                                data-perihal="{{ $order->initialWork->perihal }}"
                                                                data-tanggal="{{ optional($order->initialWork->tanggal_initial_work)->format('Y-m-d') }}"
                                                                data-keterangan-pekerjaan="{{ $order->initialWork->keterangan_pekerjaan }}"
                                                                data-functional-location='@json($order->initialWork->functional_location ?? [])'
                                                                data-scope-pekerjaan='@json($order->initialWork->scope_pekerjaan ?? [])'
                                                                data-qty='@json($order->initialWork->qty ?? [])'
                                                                data-stn='@json($order->initialWork->stn ?? [])'
                                                                data-keterangan='@json($order->initialWork->keterangan ?? [])'
                                                                title="Edit Initial Work"
                                                            >
                                                                <i data-lucide="clipboard-pen-line" class="h-3 w-3"></i>
                                                            </button>
                                                        @else
                                                            <button
                                                                type="button"
                                                                class="create-initial-work-trigger inline-flex items-center gap-1.5 rounded-xl border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[12px] font-semibold text-orange-700 transition hover:-translate-y-0.5 hover:shadow-sm"
                                                                data-mode="create"
                                                                data-action="{{ route('admin.orders.initial-work.store', $order) }}"
                                                                data-order-key="{{ $order->getRouteKey() }}"
                                                                data-nomor-order="{{ $order->nomor_order }}"
                                                                data-notifikasi="{{ $order->notifikasi }}"
                                                                data-unit-kerja="{{ $order->unit_kerja }}"
                                                                data-seksi="{{ $order->seksi }}"
                                                                data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                                data-document-number="{{ $initialWorkPreviewNumber }}"
                                                                title="Buat Initial Work"
                                                            >
                                                                <i data-lucide="clipboard-plus" class="h-3 w-3"></i>
                                                                <span>Buat Initial Work</span>
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-3 py-3">
                                            <div class="flex h-full flex-col justify-between gap-2.5">
                                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] {{ $priorityTextClasses }}">{{ $priorityLabel }}</div>
                                                        <span class="inline-flex w-max items-center rounded-full px-2 py-1 text-[10px] font-semibold {{ $noteStatusClasses }}">
                                                            {{ $order->catatan_status?->label() ?? 'Pending' }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-2 border-t border-slate-100 pt-2">
                                                        <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Catatan</div>
                                                        <div class="mt-1 text-[11px] leading-5 {{ $order->catatan ? 'text-slate-700' : 'italic text-slate-400' }}">
                                                            {{ $order->catatan ?: 'Belum ada catatan user.' }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex justify-end gap-2 pt-0.5">
                                                    <button
                                                        type="button"
                                                        class="edit-order-trigger inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-700"
                                                        data-action="{{ route('admin.orders.update', $order) }}"
                                                        data-order-key="{{ $order->getRouteKey() }}"
                                                        data-nomor-order="{{ $order->nomor_order }}"
                                                        data-notifikasi="{{ $order->notifikasi }}"
                                                        data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                        data-unit-kerja="{{ $order->unit_kerja }}"
                                                        data-prioritas="{{ $order->prioritas }}"
                                                        data-target-selesai="{{ optional($order->target_selesai)->format('Y-m-d') }}"
                                                        data-seksi="{{ $order->seksi }}"
                                                        data-catatan-status="{{ $currentNoteStatus }}"
                                                        data-catatan="{{ $order->catatan }}"
                                                        data-tanggal-order="{{ optional($order->tanggal_order)->format('Y-m-d') }}"
                                                        title="Edit"
                                                    >
                                                        <i data-lucide="pencil" class="h-[13px] w-[13px]"></i>
                                                    </button>

                                                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="delete-order-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-white shadow-sm transition hover:bg-rose-700"
                                                            title="Hapus"
                                                        >
                                                            <i data-lucide="trash-2" class="h-[13px] w-[13px]"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-14 text-center">
                                            <div class="mx-auto max-w-md space-y-2">
                                                <div class="text-base font-semibold text-slate-900">Belum ada data order.</div>
                                                <div class="text-sm text-slate-500">Gunakan tombol Buat Order untuk menambahkan order pertama.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($orders->hasPages())
                        <div class="border-t border-slate-200 px-4 py-4">
                            {{ $orders->links() }}
                        </div>
                    @endif
            </section>
        </div>

    </div>

    <div id="orderModalOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>

    <div id="createOrderModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="rounded-3xl bg-white shadow-2xl" style="width:min(100%, 860px);">
            <form method="POST" action="{{ route('admin.orders.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="form_context" value="create">
                <input type="hidden" name="tanggal_order" id="createTanggalOrder" value="{{ $today }}">
                <input type="hidden" name="deskripsi" id="createDeskripsi" value="Order pekerjaan jasa">

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-semibold text-slate-900">Order</h2>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="createNomorOrder" name="nomor_order" type="text" value="{{ old('form_context') === 'create' ? old('nomor_order') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                        @if (old('form_context') === 'create')
                            @error('nomor_order')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="createNotifikasi" name="notifikasi" type="text" value="{{ old('form_context') === 'create' ? old('notifikasi') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                        @if (old('form_context') === 'create')
                            @error('notifikasi')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nama Pekerjaan</label>
                        <input id="createNamaPekerjaan" name="nama_pekerjaan" type="text" value="{{ old('form_context') === 'create' ? old('nama_pekerjaan') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Prioritas</label>
                        <input
                            type="hidden"
                            id="createPrioritas"
                            name="prioritas"
                            value="{{ old('form_context') === 'create' ? old('prioritas', \App\Models\Order::PRIORITY_LOW) : \App\Models\Order::PRIORITY_LOW }}"
                        >
                        <div class="space-y-2">
                            <select id="createPrioritasPrimary" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                                @foreach (\App\Models\Order::priorityPrimaryOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'create' ? \App\Models\Order::priorityPrimaryFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value : $value === 'medium')>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select id="createPrioritasEmergency" class="hidden w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                @foreach (\App\Models\Order::priorityEmergencyOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'create' && \App\Models\Order::priorityEmergencyFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja</label>
                        <select id="createUnitKerja" name="unit_kerja" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($structureUnitOptions as $unitWork)
                                <option
                                    value="{{ $unitWork->name }}"
                                    @selected(old('form_context') === 'create' && old('unit_kerja') === $unitWork->name)
                                    data-seksi='@json($unitWork->sections->pluck('name')->values())'
                                >
                                    {{ $unitWork->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Seksi</label>
                        <select id="createSeksi" name="seksi" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih seksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Status Catatan</label>
                        <select id="createCatatanStatus" name="catatan_status" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            @foreach ($userNoteStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('form_context') === 'create' ? old('catatan_status', 'pending') === $value : $value === 'pending')>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if (old('form_context') === 'create')
                            @error('catatan_status')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Rencana Pemakaian</label>
                        <input id="createTargetSelesai" name="target_selesai" type="date" value="{{ old('form_context') === 'create' ? old('target_selesai', $today) : $today }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm text-slate-700">Detail Catatan</label>
                        <div class="space-y-2">
                            <select id="createCatatanSelect" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></select>
                            <textarea id="createCatatanTextarea" rows="3" placeholder="Catatan (opsional)" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                            <input type="hidden" name="catatan" id="createCatatan" value="{{ old('form_context') === 'create' ? old('catatan') : '' }}">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
                    <button type="button" data-close-order-modal class="rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editOrderModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="rounded-3xl bg-white shadow-2xl" style="width:min(100%, 860px);">
            <form method="POST" id="editOrderForm" action="#" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_context" value="edit">
                <input type="hidden" name="edit_original_order" id="editOriginalOrder" value="{{ old('edit_original_order') }}">
                <input type="hidden" name="tanggal_order" id="editTanggalOrder" value="{{ $today }}">
                <input type="hidden" name="deskripsi" id="editDeskripsi" value="Order pekerjaan jasa">

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-semibold text-slate-900">Order</h2>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="editNomorOrder" name="nomor_order" type="text" value="{{ old('form_context') === 'edit' ? old('nomor_order') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                        @if (old('form_context') === 'edit')
                            @error('nomor_order')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="editNotifikasi" name="notifikasi" type="text" value="{{ old('form_context') === 'edit' ? old('notifikasi') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                        @if (old('form_context') === 'edit')
                            @error('notifikasi')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nama Pekerjaan</label>
                        <input id="editNamaPekerjaan" name="nama_pekerjaan" type="text" value="{{ old('form_context') === 'edit' ? old('nama_pekerjaan') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Prioritas</label>
                        <input
                            type="hidden"
                            id="editPrioritas"
                            name="prioritas"
                            value="{{ old('form_context') === 'edit' ? old('prioritas', \App\Models\Order::PRIORITY_LOW) : \App\Models\Order::PRIORITY_LOW }}"
                        >
                        <div class="space-y-2">
                            <select id="editPrioritasPrimary" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                                @foreach (\App\Models\Order::priorityPrimaryOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'edit' && \App\Models\Order::priorityPrimaryFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select id="editPrioritasEmergency" class="hidden w-full rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                                @foreach (\App\Models\Order::priorityEmergencyOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form_context') === 'edit' && \App\Models\Order::priorityEmergencyFor(old('prioritas', \App\Models\Order::PRIORITY_LOW)) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja</label>
                        <select id="editUnitKerja" name="unit_kerja" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($structureUnitOptions as $unitWork)
                                <option
                                    value="{{ $unitWork->name }}"
                                    @selected(old('form_context') === 'edit' && old('unit_kerja') === $unitWork->name)
                                    data-seksi='@json($unitWork->sections->pluck('name')->values())'
                                >
                                    {{ $unitWork->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Seksi</label>
                        <select id="editSeksi" name="seksi" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih seksi</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Status Catatan</label>
                        <select id="editCatatanStatus" name="catatan_status" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            @foreach ($userNoteStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('form_context') === 'edit' && old('catatan_status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if (old('form_context') === 'edit')
                            @error('catatan_status')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Rencana Pemakaian</label>
                        <input id="editTargetSelesai" name="target_selesai" type="date" value="{{ old('form_context') === 'edit' ? old('target_selesai') : '' }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm text-slate-700">Detail Catatan</label>
                        <div class="space-y-2">
                            <select id="editCatatanSelect" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></select>
                            <textarea id="editCatatanTextarea" rows="3" placeholder="Catatan (opsional)" class="hidden w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                            <input type="hidden" name="catatan" id="editCatatan" value="{{ old('form_context') === 'edit' ? old('catatan') : '' }}">
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
                    <button type="button" data-close-order-modal class="rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="initialWorkModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="max-h-[92vh] overflow-y-auto rounded-3xl bg-white shadow-2xl" style="width:min(100%, 1080px);">
            <form method="POST" id="initialWorkForm" action="#" class="p-6">
                @csrf
                <input type="hidden" id="initialWorkMethod" name="_method" value="PUT" disabled>
                <input type="hidden" id="initialWorkFormContext" name="initial_work_form_context" value="create">
                <input type="hidden" id="initialWorkOrderKey" name="initial_work_order_key" value="{{ old('initial_work_order_key') }}">

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 id="initialWorkModalTitle" class="text-2xl font-semibold text-slate-900">Buat Initial Work</h2>
                        <p class="mt-1 text-sm text-slate-500">Dokumen initial work khusus order prioritas emergency.</p>
                    </div>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Kepada Yth</label>
                        <input id="initialWorkKepadaYth" name="kepada_yth" type="text" value="{{ old('kepada_yth') }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="PT. PRIMA KARYA MANUNGGAL">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Perihal</label>
                        <input id="initialWorkPerihal" name="perihal" type="text" value="{{ old('perihal') }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Initial Work</label>
                        <input id="initialWorkNumber" type="text" value="{{ $initialWorkPreviewNumber }}" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                        <p class="mt-1 text-[11px] text-slate-500">Nomor digenerate otomatis dan dipastikan ulang di server saat simpan.</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Tanggal Dokumen</label>
                        <input id="initialWorkTanggal" name="tanggal_initial_work" type="date" value="{{ old('tanggal_initial_work', $today) }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="initialWorkOrderNumber" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="initialWorkNotifikasi" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja Peminta</label>
                        <input id="initialWorkUnitKerja" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Seksi Peminta</label>
                        <input id="initialWorkSeksi" type="text" readonly class="w-full rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Tabel Initial Work</div>
                            <div class="mt-1 text-xs text-slate-500">Isi functional location, scope pekerjaan, qty, satuan, dan keterangan untuk kebutuhan dokumen awal.</div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="addInitialWorkRowBtn" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">Tambah Baris</button>
                            <button type="button" id="removeInitialWorkRowBtn" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">Hapus Baris</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                        <table class="min-w-full text-xs text-slate-700">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">Functional Location</th>
                                    <th class="px-3 py-2 text-left font-semibold">Scope Pekerjaan</th>
                                    <th class="px-3 py-2 text-left font-semibold">Qty</th>
                                    <th class="px-3 py-2 text-left font-semibold">Stn</th>
                                    <th class="px-3 py-2 text-left font-semibold">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="initialWorkRows"></tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm text-slate-700">Keterangan Pekerjaan / Urgensi</label>
                    <textarea id="initialWorkUrgency" name="keterangan_pekerjaan" rows="3" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="Tambahkan keterangan urgensi pekerjaan">{{ old('keterangan_pekerjaan') }}</textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-orange-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-700">Simpan Initial Work</button>
                    <button type="button" data-close-order-modal class="rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const overlay = document.getElementById('orderModalOverlay');
            const createModal = document.getElementById('createOrderModal');
            const editModal = document.getElementById('editOrderModal');
            const initialWorkModal = document.getElementById('initialWorkModal');
            const editForm = document.getElementById('editOrderForm');
            const initialWorkForm = document.getElementById('initialWorkForm');
            const initialWorkMethod = document.getElementById('initialWorkMethod');
            const swal = window.Swal;
            const createUnitKerja = document.getElementById('createUnitKerja');
            const createSeksi = document.getElementById('createSeksi');
            const editUnitKerja = document.getElementById('editUnitKerja');
            const editSeksi = document.getElementById('editSeksi');
            const createPrioritas = document.getElementById('createPrioritas');
            const createPrioritasPrimary = document.getElementById('createPrioritasPrimary');
            const createPrioritasEmergency = document.getElementById('createPrioritasEmergency');
            const editPrioritas = document.getElementById('editPrioritas');
            const editPrioritasPrimary = document.getElementById('editPrioritasPrimary');
            const editPrioritasEmergency = document.getElementById('editPrioritasEmergency');
            const createCatatanStatus = document.getElementById('createCatatanStatus');
            const editCatatanStatus = document.getElementById('editCatatanStatus');
            const oldFormContext = @json(old('form_context'));
            const oldEditOrderKey = @json(old('edit_original_order'));
            const oldInitialWorkFormContext = @json(old('initial_work_form_context'));
            const oldInitialWorkOrderKey = @json(old('initial_work_order_key'));
            const userNoteDetailOptions = @json($userNoteDetailOptions);
            const priorityUrgent = @json(\App\Models\Order::PRIORITY_URGENT);
            const priorityHigh = @json(\App\Models\Order::PRIORITY_HIGH);
            const priorityMedium = @json(\App\Models\Order::PRIORITY_MEDIUM);
            const priorityLow = @json(\App\Models\Order::PRIORITY_LOW);
            const initialWorkRowsContainer = document.getElementById('initialWorkRows');
            const initialWorkDefaultNumber = @json($initialWorkPreviewNumber);
            const initialWorkDefaultRows = @js([['functional_location' => '', 'scope_pekerjaan' => '', 'qty' => '', 'stn' => '', 'keterangan' => '']]);

            const parseSeksiOptions = (select) => {
                const selectedOption = select?.options?.[select.selectedIndex];
                const raw = selectedOption?.dataset?.seksi;

                if (!raw) {
                    return [];
                }

                try {
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const syncSeksiSelect = (unitSelect, seksiSelect, selectedValue = '') => {
                if (!unitSelect || !seksiSelect) {
                    return;
                }

                const seksiOptions = parseSeksiOptions(unitSelect);
                const normalizedValue = selectedValue === 'General' ? 'Tidak ada seksi' : selectedValue;
                const normalizedOptions = seksiOptions.length > 0 ? seksiOptions : ['Tidak ada seksi'];
                const fallbackValue = seksiOptions.length > 0
                    ? (normalizedOptions.includes(normalizedValue) ? normalizedValue : normalizedOptions[0])
                    : (normalizedValue || 'Tidak ada seksi');

                seksiSelect.innerHTML = '';

                normalizedOptions.forEach((optionValue) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionValue;
                    if (optionValue === fallbackValue) {
                        option.selected = true;
                    }
                    seksiSelect.appendChild(option);
                });

                if (seksiOptions.length === 0 && !normalizedOptions.includes(fallbackValue) && fallbackValue) {
                    const fallbackOption = document.createElement('option');
                    fallbackOption.value = fallbackValue;
                    fallbackOption.textContent = fallbackValue;
                    fallbackOption.selected = true;
                    seksiSelect.appendChild(fallbackOption);
                }
            };

            const showAlert = (options) => {
                if (swal) {
                    swal.fire(options);
                    return;
                }

                alert(options.text || options.title || 'Terjadi kesalahan.');
            };

            const showToast = (message, icon = 'success') => {
                if (swal) {
                    swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon,
                        title: message,
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                    });
                    return;
                }

                alert(message);
            };

            const syncModalNoteField = (context, selectedStatus = 'pending', currentNote = '') => {
                const statusSelect = document.getElementById(`${context}CatatanStatus`);
                const detailSelect = document.getElementById(`${context}CatatanSelect`);
                const detailTextarea = document.getElementById(`${context}CatatanTextarea`);
                const hiddenInput = document.getElementById(`${context}Catatan`);

                if (!statusSelect || !detailSelect || !detailTextarea || !hiddenInput) {
                    return;
                }

                const detailOptions = userNoteDetailOptions[selectedStatus] || [];
                const useSelect = detailOptions.length > 0;

                detailSelect.innerHTML = '';

                if (useSelect) {
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = selectedStatus === 'approved_workshop'
                        ? '- Pilih regu workshop (opsional) -'
                        : selectedStatus === 'approved_jasa'
                            ? '- Pilih jenis jasa (opsional) -'
                            : '- Pilih (opsional) -';
                    detailSelect.appendChild(placeholder);

                    detailOptions.forEach((optionValue) => {
                        const option = document.createElement('option');
                        option.value = optionValue;
                        option.textContent = optionValue;
                        option.selected = currentNote === optionValue;
                        detailSelect.appendChild(option);
                    });

                    detailSelect.classList.remove('hidden');
                    detailSelect.disabled = false;
                    detailTextarea.classList.add('hidden');
                    detailTextarea.disabled = true;
                    detailTextarea.value = '';
                    detailSelect.value = currentNote;
                    hiddenInput.value = detailSelect.value || '';
                    return;
                }

                detailSelect.classList.add('hidden');
                detailSelect.disabled = true;
                detailTextarea.classList.remove('hidden');
                detailTextarea.disabled = false;
                detailTextarea.value = currentNote;
                hiddenInput.value = detailTextarea.value || '';
            };

            const bindModalNoteField = (context) => {
                const statusSelect = document.getElementById(`${context}CatatanStatus`);
                const detailSelect = document.getElementById(`${context}CatatanSelect`);
                const detailTextarea = document.getElementById(`${context}CatatanTextarea`);
                const hiddenInput = document.getElementById(`${context}Catatan`);

                statusSelect?.addEventListener('change', () => {
                    syncModalNoteField(context, statusSelect.value, '');
                });

                detailSelect?.addEventListener('change', () => {
                    if (hiddenInput) {
                        hiddenInput.value = detailSelect.value || '';
                    }
                });

                detailTextarea?.addEventListener('input', () => {
                    if (hiddenInput) {
                        hiddenInput.value = detailTextarea.value || '';
                    }
                });
            };

            const syncPriorityField = (context, currentPriority = priorityLow) => {
                const hiddenInput = document.getElementById(`${context}Prioritas`);
                const primarySelect = document.getElementById(`${context}PrioritasPrimary`);
                const emergencySelect = document.getElementById(`${context}PrioritasEmergency`);

                if (!hiddenInput || !primarySelect || !emergencySelect) {
                    return;
                }

                const primaryValue = currentPriority === priorityUrgent || currentPriority === priorityHigh
                    ? 'emergency'
                    : currentPriority === priorityMedium
                        ? 'high'
                        : 'medium';

                primarySelect.value = primaryValue;

                if (primaryValue === 'emergency') {
                    emergencySelect.classList.remove('hidden');
                    emergencySelect.disabled = false;
                    emergencySelect.value = currentPriority === priorityUrgent ? priorityUrgent : priorityHigh;
                    hiddenInput.value = emergencySelect.value;
                    return;
                }

                emergencySelect.classList.add('hidden');
                emergencySelect.disabled = true;
                emergencySelect.value = priorityHigh;
                hiddenInput.value = primaryValue === 'high' ? priorityMedium : priorityLow;
            };

            const bindPriorityField = (context) => {
                const hiddenInput = document.getElementById(`${context}Prioritas`);
                const primarySelect = document.getElementById(`${context}PrioritasPrimary`);
                const emergencySelect = document.getElementById(`${context}PrioritasEmergency`);

                if (!hiddenInput || !primarySelect || !emergencySelect) {
                    return;
                }

                primarySelect.addEventListener('change', () => {
                    if (primarySelect.value === 'emergency') {
                        emergencySelect.classList.remove('hidden');
                        emergencySelect.disabled = false;
                        if (!emergencySelect.value) {
                            emergencySelect.value = priorityHigh;
                        }
                        hiddenInput.value = emergencySelect.value || priorityHigh;
                        return;
                    }

                    emergencySelect.classList.add('hidden');
                    emergencySelect.disabled = true;
                    hiddenInput.value = primarySelect.value === 'high' ? priorityMedium : priorityLow;
                });

                emergencySelect.addEventListener('change', () => {
                    hiddenInput.value = emergencySelect.value || priorityHigh;
                });
            };

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const parseArrayData = (rawValue) => {
                if (!rawValue) {
                    return [];
                }

                try {
                    const parsed = JSON.parse(rawValue);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const renderInitialWorkRows = (rows = initialWorkDefaultRows) => {
                if (!initialWorkRowsContainer) {
                    return;
                }

                const normalizedRows = Array.isArray(rows) && rows.length > 0 ? rows : initialWorkDefaultRows;

                initialWorkRowsContainer.innerHTML = normalizedRows.map((row, index) => `
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2">
                            <input name="functional_location[]" type="text" value="${escapeHtml(row.functional_location)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="scope_pekerjaan[]" type="text" value="${escapeHtml(row.scope_pekerjaan)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="qty[]" type="text" value="${escapeHtml(row.qty)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="stn[]" type="text" value="${escapeHtml(row.stn)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none" required>
                        </td>
                        <td class="px-3 py-2">
                            <input name="keterangan[]" type="text" value="${escapeHtml(row.keterangan)}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-orange-500 focus:outline-none">
                        </td>
                    </tr>
                `).join('');
            };

            const addInitialWorkRow = () => {
                const rows = Array.from(initialWorkRowsContainer?.querySelectorAll('tr') || []).map((row) => ({
                    functional_location: row.querySelector('[name="functional_location[]"]')?.value || '',
                    scope_pekerjaan: row.querySelector('[name="scope_pekerjaan[]"]')?.value || '',
                    qty: row.querySelector('[name="qty[]"]')?.value || '',
                    stn: row.querySelector('[name="stn[]"]')?.value || '',
                    keterangan: row.querySelector('[name="keterangan[]"]')?.value || '',
                }));

                rows.push({ functional_location: '', scope_pekerjaan: '', qty: '', stn: '', keterangan: '' });
                renderInitialWorkRows(rows);
            };

            const removeInitialWorkRow = () => {
                const rows = Array.from(initialWorkRowsContainer?.querySelectorAll('tr') || []).map((row) => ({
                    functional_location: row.querySelector('[name="functional_location[]"]')?.value || '',
                    scope_pekerjaan: row.querySelector('[name="scope_pekerjaan[]"]')?.value || '',
                    qty: row.querySelector('[name="qty[]"]')?.value || '',
                    stn: row.querySelector('[name="stn[]"]')?.value || '',
                    keterangan: row.querySelector('[name="keterangan[]"]')?.value || '',
                }));

                if (rows.length <= 1) {
                    return;
                }

                rows.pop();
                renderInitialWorkRows(rows);
            };

            const setInitialWorkModalState = (button, mode = 'create', rows = null) => {
                if (!initialWorkForm || !button) {
                    return;
                }

                const isEdit = mode === 'edit';
                initialWorkForm.action = button.dataset.action || '#';
                initialWorkMethod.disabled = !isEdit;
                initialWorkMethod.value = 'PUT';
                document.getElementById('initialWorkFormContext').value = isEdit ? 'edit' : 'create';
                document.getElementById('initialWorkOrderKey').value = button.dataset.orderKey || '';
                document.getElementById('initialWorkModalTitle').textContent = isEdit ? 'Edit Initial Work' : 'Buat Initial Work';
                document.getElementById('initialWorkNumber').value = button.dataset.documentNumber || initialWorkDefaultNumber;
                document.getElementById('initialWorkOrderNumber').value = button.dataset.nomorOrder || '';
                document.getElementById('initialWorkNotifikasi').value = button.dataset.notifikasi || '-';
                document.getElementById('initialWorkUnitKerja').value = button.dataset.unitKerja || '-';
                document.getElementById('initialWorkSeksi').value = button.dataset.seksi || '-';
                document.getElementById('initialWorkKepadaYth').value = button.dataset.kepadaYth || 'PT. PRIMA KARYA MANUNGGAL';
                document.getElementById('initialWorkPerihal').value = button.dataset.perihal || `Initial Work - ${button.dataset.namaPekerjaan || button.dataset.nomorOrder || ''}`;
                document.getElementById('initialWorkTanggal').value = button.dataset.tanggal || '{{ $today }}';
                document.getElementById('initialWorkUrgency').value = button.dataset.keteranganPekerjaan || '';

                if (rows) {
                    renderInitialWorkRows(rows);
                    return;
                }

                if (isEdit) {
                    const functionalLocations = parseArrayData(button.dataset.functionalLocation);
                    const scopePekerjaan = parseArrayData(button.dataset.scopePekerjaan);
                    const qty = parseArrayData(button.dataset.qty);
                    const stn = parseArrayData(button.dataset.stn);
                    const keterangan = parseArrayData(button.dataset.keterangan);
                    const totalRows = Math.max(functionalLocations.length, scopePekerjaan.length, qty.length, stn.length, keterangan.length, 1);

                    renderInitialWorkRows(Array.from({ length: totalRows }, (_, index) => ({
                        functional_location: functionalLocations[index] || '',
                        scope_pekerjaan: scopePekerjaan[index] || '',
                        qty: qty[index] || '',
                        stn: stn[index] || '',
                        keterangan: keterangan[index] || '',
                    })));

                    return;
                }

                renderInitialWorkRows(initialWorkDefaultRows);
            };

            bindModalNoteField('create');
            bindModalNoteField('edit');
            bindPriorityField('create');
            bindPriorityField('edit');

            const openModal = (modal) => {
                overlay?.classList.remove('hidden');
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModals = () => {
                overlay?.classList.add('hidden');
                [createModal, editModal, initialWorkModal].forEach((modal) => {
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                });
                document.body.classList.remove('overflow-hidden');
            };

            document.getElementById('openCreateOrderModal')?.addEventListener('click', () => {
                document.getElementById('createNomorOrder').value = '';
                document.getElementById('createNotifikasi').value = '';
                document.getElementById('createNamaPekerjaan').value = '';
                createUnitKerja.value = '';
                    syncPriorityField('create', priorityLow);
                    createCatatanStatus.value = 'pending';
                document.getElementById('createTargetSelesai').value = '{{ $today }}';
                document.getElementById('createTanggalOrder').value = '{{ $today }}';
                document.getElementById('createDeskripsi').value = 'Order pekerjaan jasa';
                document.getElementById('createCatatan').value = '';
                createSeksi.innerHTML = '<option value="">Pilih seksi</option>';
                syncModalNoteField('create', 'pending', '');
                openModal(createModal);
            });

            createUnitKerja?.addEventListener('change', () => {
                syncSeksiSelect(createUnitKerja, createSeksi);
            });

            editUnitKerja?.addEventListener('change', () => {
                syncSeksiSelect(editUnitKerja, editSeksi);
            });

            document.querySelectorAll('.edit-order-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!editForm) return;

                    editForm.action = button.dataset.action || '#';
                    document.getElementById('editOriginalOrder').value = button.dataset.orderKey || '';
                    document.getElementById('editNomorOrder').value = button.dataset.nomorOrder || '';
                    document.getElementById('editNotifikasi').value = button.dataset.notifikasi || '';
                    document.getElementById('editNamaPekerjaan').value = button.dataset.namaPekerjaan || '';
                    editUnitKerja.value = button.dataset.unitKerja || '';
                    syncPriorityField('edit', button.dataset.prioritas || priorityLow);
                    editCatatanStatus.value = button.dataset.catatanStatus || 'pending';
                    document.getElementById('editTargetSelesai').value = button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editTanggalOrder').value = button.dataset.tanggalOrder || button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editCatatan').value = button.dataset.catatan || '';
                    document.getElementById('editDeskripsi').value = button.dataset.namaPekerjaan || 'Order pekerjaan jasa';
                    syncSeksiSelect(editUnitKerja, editSeksi, button.dataset.seksi || 'Tidak ada seksi');
                    syncModalNoteField('edit', button.dataset.catatanStatus || 'pending', button.dataset.catatan || '');

                    openModal(editModal);
                });
            });

            document.getElementById('addInitialWorkRowBtn')?.addEventListener('click', addInitialWorkRow);
            document.getElementById('removeInitialWorkRowBtn')?.addEventListener('click', removeInitialWorkRow);

            document.querySelectorAll('.create-initial-work-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    setInitialWorkModalState(button, 'create');
                    openModal(initialWorkModal);
                });
            });

            document.querySelectorAll('.edit-initial-work-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    setInitialWorkModalState(button, 'edit');
                    openModal(initialWorkModal);
                });
            });

            if (oldFormContext === 'create') {
                syncSeksiSelect(createUnitKerja, createSeksi, @json(old('seksi')));
                syncPriorityField('create', @json(old('prioritas', \App\Models\Order::PRIORITY_LOW)));
                syncModalNoteField('create', @json(old('catatan_status', 'pending')), @json(old('catatan', '')));
                openModal(createModal);
            }

            if (oldFormContext === 'edit') {
                const editTrigger = oldEditOrderKey
                    ? document.querySelector(`.edit-order-trigger[data-order-key="${oldEditOrderKey}"]`)
                    : null;

                if (editTrigger && editForm) {
                    editForm.action = editTrigger.dataset.action || '#';
                    document.getElementById('editOriginalOrder').value = oldEditOrderKey || '';
                    document.getElementById('editTanggalOrder').value = @json(old('tanggal_order', $today));
                    document.getElementById('editCatatan').value = @json(old('catatan', ''));
                    document.getElementById('editDeskripsi').value = @json(old('deskripsi', 'Order pekerjaan jasa'));
                    editCatatanStatus.value = @json(old('catatan_status', 'pending'));
                    syncPriorityField('edit', @json(old('prioritas', \App\Models\Order::PRIORITY_LOW)));
                    syncSeksiSelect(editUnitKerja, editSeksi, @json(old('seksi')));
                    syncModalNoteField('edit', @json(old('catatan_status', 'pending')), @json(old('catatan', '')));
                    openModal(editModal);
                }
            }

            if (oldInitialWorkFormContext === 'create' || oldInitialWorkFormContext === 'edit') {
                const triggerSelector = oldInitialWorkFormContext === 'edit'
                    ? `.edit-initial-work-trigger[data-order-key="${oldInitialWorkOrderKey}"]`
                    : `.create-initial-work-trigger[data-order-key="${oldInitialWorkOrderKey}"]`;
                const initialWorkTrigger = oldInitialWorkOrderKey ? document.querySelector(triggerSelector) : null;

                if (initialWorkTrigger) {
                    const functionalLocations = @json(old('functional_location', []));
                    const scopePekerjaan = @json(old('scope_pekerjaan', []));
                    const qty = @json(old('qty', []));
                    const stn = @json(old('stn', []));
                    const keterangan = @json(old('keterangan', []));
                    const totalRows = Math.max(functionalLocations.length, scopePekerjaan.length, qty.length, stn.length, keterangan.length, 1);
                    const oldRows = Array.from({ length: totalRows }, (_, index) => ({
                        functional_location: functionalLocations[index] || '',
                        scope_pekerjaan: scopePekerjaan[index] || '',
                        qty: qty[index] || '',
                        stn: stn[index] || '',
                        keterangan: keterangan[index] || '',
                    }));

                    setInitialWorkModalState(initialWorkTrigger, oldInitialWorkFormContext, oldRows);
                    document.getElementById('initialWorkKepadaYth').value = @json(old('kepada_yth', 'PT. PRIMA KARYA MANUNGGAL'));
                    document.getElementById('initialWorkPerihal').value = @json(old('perihal', ''));
                    document.getElementById('initialWorkTanggal').value = @json(old('tanggal_initial_work', $today));
                    document.getElementById('initialWorkUrgency').value = @json(old('keterangan_pekerjaan', ''));
                    openModal(initialWorkModal);
                }
            }

            overlay?.addEventListener('click', closeModals);

            document.querySelectorAll('[data-close-order-modal]').forEach((button) => {
                button.addEventListener('click', closeModals);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModals();
                }
            });

            const successFlash = document.getElementById('flash-success');
            const errorFlash = document.getElementById('flash-error');

            if (successFlash?.dataset.message) {
                showAlert({
                    icon: 'success',
                    title: 'Berhasil',
                    text: successFlash.dataset.message,
                    timer: 1600,
                    showConfirmButton: false,
                });
            }

            if (errorFlash?.dataset.message) {
                showAlert({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorFlash.dataset.message,
                });
            }

            document.querySelectorAll('.delete-order-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (!swal) {
                        if (confirm('Hapus order ini?')) {
                            form.submit();
                        }
                        return;
                    }

                    const result = await swal.fire({
                        icon: 'warning',
                        title: 'Hapus order?',
                        text: 'Data order akan dihapus permanen.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#e11d48',
                    });

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
