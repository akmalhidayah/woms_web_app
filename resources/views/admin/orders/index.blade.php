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
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase">Catatan User</th>
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
                                        <td class="px-4 py-4 text-[11px] font-semibold text-slate-600">
                                            <div class="inline-flex min-w-[102px] flex-col items-start justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-[15px] font-semibold text-slate-800 shadow-sm">
                                                <span>{{ $order->nomor_order }}</span>
                                                @if ($order->notifikasi)
                                                    <span class="mt-1 text-[11px] font-medium text-blue-600">Notif: {{ $order->notifikasi }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="grid gap-3">
                                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                                                    <div class="text-[15px] font-semibold leading-snug text-slate-900">{{ $order->nama_pekerjaan }}</div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2 text-[13px] text-slate-600">
                                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5">
                                                        <span class="font-semibold text-slate-700">Unit:</span>
                                                        <span>{{ $order->unit_kerja }}</span>
                                                    </span>

                                                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-blue-700">
                                                        <span class="font-semibold text-blue-800">Seksi:</span>
                                                        <span>{{ $order->seksi }}</span>
                                                    </span>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-3 text-[13px] text-slate-700">
                                                    <span><span class="font-semibold">Tanggal:</span> {{ $order->tanggal_order->format('Y-m-d') }}</span>
                                                    <form action="{{ route('admin.orders.priority.update', $order) }}" method="POST" class="priority-update-form flex items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select
                                                            name="prioritas"
                                                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none"
                                                        >
                                                            @foreach ($priorityControlOptions as $value => $label)
                                                                <option value="{{ $value }}" @selected($order->prioritas === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button
                                                            type="submit"
                                                            class="rounded-md bg-slate-500 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-slate-600"
                                                        >
                                                            Update
                                                        </button>
                                                    </form>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                                    @foreach ($documentIndicators as $indicator)
                                                        @if ($indicator['available'] && $indicator['url'])
                                                            <a
                                                                href="{{ $indicator['url'] }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="inline-flex items-center gap-2 rounded-2xl border px-3 py-2 text-[13px] font-semibold transition hover:-translate-y-0.5 hover:shadow-sm {{ $indicator['classes'] }}"
                                                                title="Buka {{ $indicator['label'] }}"
                                                            >
                                                                <i data-lucide="{{ $indicator['icon'] }}" class="h-[13px] w-[13px]"></i>
                                                                <span>{{ $indicator['label'] }}</span>
                                                                <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-semibold">
                                                                    Ada
                                                                </span>
                                                            </a>
                                                        @else
                                                            <span class="inline-flex items-center gap-2 rounded-2xl border px-3 py-2 text-[13px] font-semibold {{ $indicator['classes'] }}">
                                                                <i data-lucide="{{ $indicator['icon'] }}" class="h-[13px] w-[13px]"></i>
                                                                <span>{{ $indicator['label'] }}</span>
                                                                <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-semibold">
                                                                    Belum
                                                                </span>
                                                            </span>
                                                        @endif
                                                    @endforeach

                                                    <a
                                                        href="{{ route('admin.orders.show', $order) }}"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                        title="Edit Dokumen"
                                                    >
                                                        <i data-lucide="folder-open" class="h-[13px] w-[13px]"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="flex h-full flex-col justify-between gap-4">
                                                <form action="{{ route('admin.orders.user-note.update', $order) }}" method="POST" class="user-note-form space-y-3">
                                                    @csrf
                                                    @method('PATCH')

                                                    <select
                                                        name="catatan_status"
                                                        class="user-note-status-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none"
                                                    >
                                                        @foreach ($userNoteStatusOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected($currentNoteStatus === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>

                                                    <div
                                                        class="user-note-detail-wrapper"
                                                        data-detail-options='@json($userNoteDetailOptions)'
                                                        data-current-status="{{ $currentNoteStatus }}"
                                                        data-current-note="{{ $order->catatan }}"
                                                    >
                                                        <select
                                                            name="catatan"
                                                            class="user-note-detail-select hidden w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none"
                                                        >
                                                            <option value="">- Pilih (opsional) -</option>
                                                            @foreach ($noteDetailOptions as $option)
                                                                <option value="{{ $option }}" @selected($order->catatan === $option)>{{ $option }}</option>
                                                            @endforeach
                                                        </select>

                                                        <textarea
                                                            name="catatan"
                                                            rows="2"
                                                            placeholder="Catatan (opsional)"
                                                            class="user-note-detail-textarea hidden w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-[13px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
                                                        >{{ $order->catatan }}</textarea>
                                                    </div>

                                                    <button
                                                        type="submit"
                                                        class="w-full rounded-full bg-slate-500 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-slate-600"
                                                    >
                                                        Save
                                                    </button>
                                                </form>

                                                <div class="flex justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        class="edit-order-trigger inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700"
                                                        data-action="{{ route('admin.orders.update', $order) }}"
                                                        data-nomor-order="{{ $order->nomor_order }}"
                                                        data-notifikasi="{{ $order->notifikasi }}"
                                                        data-nama-pekerjaan="{{ $order->nama_pekerjaan }}"
                                                        data-unit-kerja="{{ $order->unit_kerja }}"
                                                        data-prioritas="{{ $order->prioritas }}"
                                                        data-target-selesai="{{ optional($order->target_selesai)->format('Y-m-d') }}"
                                                        data-seksi="{{ $order->seksi }}"
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
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-white transition hover:bg-rose-700"
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
        <div class="rounded-3xl bg-white shadow-2xl" style="width:min(100%, 640px);">
            <form method="POST" action="{{ route('admin.orders.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="tanggal_order" id="createTanggalOrder" value="{{ $today }}">
                <input type="hidden" name="deskripsi" id="createDeskripsi" value="Order pekerjaan jasa">
                <input type="hidden" name="catatan" value="">

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-semibold text-slate-900">Order</h2>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-5">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="createNomorOrder" name="nomor_order" type="text" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="createNotifikasi" name="notifikasi" type="text" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nama Pekerjaan</label>
                        <input id="createNamaPekerjaan" name="nama_pekerjaan" type="text" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja</label>
                        <select id="createUnitKerja" name="unit_kerja" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($structureUnitOptions as $unitWork)
                                <option
                                    value="{{ $unitWork->name }}"
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
                        <label class="mb-2 block text-sm text-slate-700">Prioritas</label>
                        <select id="createPrioritas" name="prioritas" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            @foreach (\App\Models\Order::priorityOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Rencana Pemakaian</label>
                        <input id="createTargetSelesai" name="target_selesai" type="date" value="{{ $today }}" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
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
        <div class="rounded-3xl bg-white shadow-2xl" style="width:min(100%, 640px);">
            <form method="POST" id="editOrderForm" action="#" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="tanggal_order" id="editTanggalOrder" value="{{ $today }}">
                <input type="hidden" name="deskripsi" id="editDeskripsi" value="Order pekerjaan jasa">
                <input type="hidden" name="catatan" id="editCatatan" value="">

                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-semibold text-slate-900">Order</h2>
                    <button type="button" data-close-order-modal class="text-2xl text-slate-500 transition hover:text-slate-700">&times;</button>
                </div>

                <div class="mt-6 grid gap-5">
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nomor Order</label>
                        <input id="editNomorOrder" name="nomor_order" type="text" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Notifikasi</label>
                        <input id="editNotifikasi" name="notifikasi" type="text" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Nama Pekerjaan</label>
                        <input id="editNamaPekerjaan" name="nama_pekerjaan" type="text" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Unit Kerja</label>
                        <select id="editUnitKerja" name="unit_kerja" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            <option value="">Pilih Unit Kerja</option>
                            @foreach ($structureUnitOptions as $unitWork)
                                <option
                                    value="{{ $unitWork->name }}"
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
                        <label class="mb-2 block text-sm text-slate-700">Prioritas</label>
                        <select id="editPrioritas" name="prioritas" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                            @foreach (\App\Models\Order::priorityOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-slate-700">Rencana Pemakaian</label>
                        <input id="editTargetSelesai" name="target_selesai" type="date" class="w-full rounded-lg border border-slate-400 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none" required>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">Submit</button>
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
            const editForm = document.getElementById('editOrderForm');
            const swal = window.Swal;
            const createUnitKerja = document.getElementById('createUnitKerja');
            const createSeksi = document.getElementById('createSeksi');
            const editUnitKerja = document.getElementById('editUnitKerja');
            const editSeksi = document.getElementById('editSeksi');

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

            const syncUserNoteField = (wrapper) => {
                if (!wrapper) return;

                const statusSelect = wrapper.closest('form')?.querySelector('.user-note-status-select');
                const detailSelect = wrapper.querySelector('.user-note-detail-select');
                const detailTextarea = wrapper.querySelector('.user-note-detail-textarea');
                const optionsMap = JSON.parse(wrapper.dataset.detailOptions || '{}');
                const selectedStatus = statusSelect?.value || wrapper.dataset.currentStatus || 'pending';
                const currentNote = wrapper.dataset.currentNote || '';
                const detailOptions = optionsMap[selectedStatus] || [];
                const useSelect = detailOptions.length > 0;

                if (detailSelect) {
                    detailSelect.innerHTML = '';
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
                        if (currentNote === optionValue) {
                            option.selected = true;
                        }
                        detailSelect.appendChild(option);
                    });

                    detailSelect.classList.toggle('hidden', !useSelect);
                    detailSelect.disabled = !useSelect;
                }

                if (detailTextarea) {
                    detailTextarea.classList.toggle('hidden', useSelect);
                    detailTextarea.disabled = useSelect;
                    if (!useSelect) {
                        detailTextarea.value = currentNote;
                    }
                }
            };

            document.querySelectorAll('.user-note-detail-wrapper').forEach((wrapper) => {
                syncUserNoteField(wrapper);

                const statusSelect = wrapper.closest('form')?.querySelector('.user-note-status-select');
                statusSelect?.addEventListener('change', () => {
                    wrapper.dataset.currentStatus = statusSelect.value;
                    wrapper.dataset.currentNote = '';
                    syncUserNoteField(wrapper);
                });

                wrapper.querySelector('.user-note-detail-select')?.addEventListener('change', (event) => {
                    wrapper.dataset.currentNote = event.target.value;
                });

                wrapper.querySelector('.user-note-detail-textarea')?.addEventListener('input', (event) => {
                    wrapper.dataset.currentNote = event.target.value;
                });
            });

            const openModal = (modal) => {
                overlay?.classList.remove('hidden');
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModals = () => {
                overlay?.classList.add('hidden');
                [createModal, editModal].forEach((modal) => {
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
                document.getElementById('createPrioritas').value = 'sedang';
                document.getElementById('createTargetSelesai').value = '{{ $today }}';
                document.getElementById('createTanggalOrder').value = '{{ $today }}';
                document.getElementById('createDeskripsi').value = 'Order pekerjaan jasa';
                createSeksi.innerHTML = '<option value="">Pilih seksi</option>';
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
                    document.getElementById('editNomorOrder').value = button.dataset.nomorOrder || '';
                    document.getElementById('editNotifikasi').value = button.dataset.notifikasi || '';
                    document.getElementById('editNamaPekerjaan').value = button.dataset.namaPekerjaan || '';
                    editUnitKerja.value = button.dataset.unitKerja || '';
                    document.getElementById('editPrioritas').value = button.dataset.prioritas || 'sedang';
                    document.getElementById('editTargetSelesai').value = button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editTanggalOrder').value = button.dataset.tanggalOrder || button.dataset.targetSelesai || '{{ $today }}';
                    document.getElementById('editCatatan').value = button.dataset.catatan || '';
                    document.getElementById('editDeskripsi').value = button.dataset.namaPekerjaan || 'Order pekerjaan jasa';
                    syncSeksiSelect(editUnitKerja, editSeksi, button.dataset.seksi || 'Tidak ada seksi');

                    openModal(editModal);
                });
            });

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

            document.querySelectorAll('.priority-update-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitButton = form.querySelector('button[type="submit"]');
                    const formData = new FormData(form);

                    submitButton?.setAttribute('disabled', 'disabled');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(data?.message || data?.error || 'Gagal memperbarui prioritas order.');
                        }

                        showToast(data?.message || 'Prioritas order berhasil diperbarui.');
                    } catch (error) {
                        showAlert({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message || 'Gagal memperbarui prioritas order.',
                        });
                    } finally {
                        submitButton?.removeAttribute('disabled');
                    }
                });
            });

            document.querySelectorAll('.user-note-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitButton = form.querySelector('button[type="submit"]');
                    const formData = new FormData(form);

                    submitButton?.setAttribute('disabled', 'disabled');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(data?.message || data?.error || 'Gagal menyimpan catatan user.');
                        }

                        showToast(data?.message || 'Catatan user berhasil diperbarui.');
                    } catch (error) {
                        showAlert({
                            icon: 'error',
                            title: 'Gagal',
                            text: error.message || 'Gagal menyimpan catatan user.',
                        });
                    } finally {
                        submitButton?.removeAttribute('disabled');
                    }
                });
            });

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
