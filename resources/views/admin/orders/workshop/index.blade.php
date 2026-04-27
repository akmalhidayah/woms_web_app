<x-layouts.admin title="Order Pekerjaan Bengkel">
    <div class="space-y-6">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="factory" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">Order Pekerjaan Bengkel</h1>
                    <p class="mt-1.5 text-[13px] text-slate-500">Order yang diarahkan ke bengkel dari status workshop dan workshop + jasa.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <form method="GET" action="{{ route('admin.orders.workshop.index') }}" class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="grid flex-1 gap-2.5 md:grid-cols-4 xl:grid-cols-[1.2fr_0.9fr_0.8fr_0.8fr]">
                        <div class="flex flex-col">
                            <label for="search" class="mb-1.5 text-[10px] font-semibold text-slate-700">Pencarian</label>
                            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor / pekerjaan / unit..." class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div class="flex flex-col">
                            <label for="progress" class="mb-1.5 text-[10px] font-semibold text-slate-700">Progress</label>
                            <select id="progress" name="progress" class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Progress</option>
                                @foreach ($progressOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedProgress === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label for="regu" class="mb-1.5 text-[10px] font-semibold text-slate-700">Regu</label>
                            <select id="regu" name="regu" class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Regu</option>
                                @foreach ($reguOptions as $regu)
                                    <option value="{{ $regu }}" @selected($selectedRegu === $regu)>{{ $regu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label for="perPage" class="mb-1.5 text-[10px] font-semibold text-slate-700">Per Halaman</label>
                            <select id="perPage" name="perPage" class="rounded-lg border border-slate-300 px-3 py-2 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                @foreach ([10, 25, 50] as $option)
                                    <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-[13px] w-[13px]"></i>
                        </button>
                        <a href="{{ route('admin.orders.workshop.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[13px] w-[13px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-[10px] text-slate-700 order-workshop-table">
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Nomor Order</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Deskripsi & Dokumen</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Unit / Seksi</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Konfirmasi Anggaran</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Status Material</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Progress Pekerjaan</th>
                            <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($orders as $order)
                            @php
                                $abnormalDocument = $order->documents->firstWhere('jenis_dokumen.value', 'abnormalitas');
                                $gambarDocument = $order->documents->firstWhere('jenis_dokumen.value', 'gambar_teknik');
                                $workshop = $order->orderWorkshop;
                                $konfirmasi = $workshop?->konfirmasi_anggaran;
                                $showMaterial = $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY;
                                $showProgress = in_array($konfirmasi, [
                                    \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY,
                                    \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY,
                                ], true);
                                $showEkorin = $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY;
                                $workshopSummary = match (true) {
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_DONE => 'Selesai',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY => 'Material Ready',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY => 'E-Korin',
                                    default => 'Belum Konfirmasi',
                                };
                                $workshopSummaryClasses = match (true) {
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_DONE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY => 'border-sky-200 bg-sky-50 text-sky-700',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY => 'border-amber-200 bg-amber-50 text-amber-700',
                                    default => 'border-slate-200 bg-slate-50 text-slate-500',
                                };
                                $workshopNextStep = match (true) {
                                    blank($konfirmasi) => 'Pilih konfirmasi anggaran/material.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_READY && blank($workshop?->status_material) => 'Isi status material.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY && blank($workshop?->nomor_e_korin) => 'Isi nomor E-Korin.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY && blank($workshop?->status_anggaran) => 'Isi status anggaran.',
                                    $konfirmasi === \App\Models\OrderWorkshop::KONFIRMASI_MATERIAL_NOT_READY && blank($workshop?->status_e_korin) => 'Isi status E-Korin.',
                                    $showProgress && blank($workshop?->progress_status) => 'Update progress bengkel.',
                                    $workshop?->progress_status === \App\Models\OrderWorkshop::PROGRESS_DONE => 'Pekerjaan bengkel selesai.',
                                    default => 'Pantau catatan dan progress bengkel.',
                                };
                                $workshopFlowChecklist = [
                                    ['label' => 'Konfirmasi', 'value' => $konfirmasi ?: '-', 'ready' => filled($konfirmasi)],
                                    ['label' => 'E-Korin', 'value' => $workshop?->nomor_e_korin ?: ($showEkorin ? '-' : 'N/A'), 'ready' => ! $showEkorin || filled($workshop?->nomor_e_korin)],
                                    ['label' => 'Status Anggaran', 'value' => $workshop?->status_anggaran ?: ($showEkorin ? '-' : 'N/A'), 'ready' => ! $showEkorin || filled($workshop?->status_anggaran)],
                                    ['label' => 'Status Material', 'value' => $workshop?->status_material ?: ($showMaterial ? '-' : 'N/A'), 'ready' => ! $showMaterial || filled($workshop?->status_material)],
                                    ['label' => 'Progress', 'value' => $progressOptions[$workshop?->progress_status] ?? '-', 'ready' => filled($workshop?->progress_status)],
                                ];
                            @endphp
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-800">{{ $order->nomor_order }}</div>
                                    <div class="mt-1 text-[9px] text-slate-400">Tanggal: {{ optional($order->tanggal_order)->format('d-m-Y') ?: '-' }}</div>
                                    <button
                                        type="button"
                                        class="workshop-flow-trigger mt-2 inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[9px] font-semibold transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 {{ $workshopSummaryClasses }}"
                                        data-title="{{ $order->nomor_order }}"
                                        data-summary="{{ $workshopSummary }}"
                                        data-next="{{ $workshopNextStep }}"
                                        data-checklist='@json($workshopFlowChecklist)'
                                    >
                                        {{ $workshopSummary }}
                                    </button>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-800">{{ \Illuminate\Support\Str::limit($order->nama_pekerjaan, 180) }}</div>
                                    <div class="mt-2 flex flex-col gap-2">
                                        @if ($abnormalDocument)
                                            <a href="{{ route('admin.orders.documents.preview', [$order, $abnormalDocument]) }}" target="_blank" class="inline-flex w-max items-center gap-2 rounded-lg bg-red-500 px-3 py-1.5 text-[10px] font-semibold text-white transition hover:bg-red-600">
                                                <i data-lucide="file-text" class="h-3 w-3"></i>
                                                <span>Abnormalitas</span>
                                            </a>
                                        @endif
                                        @if ($gambarDocument)
                                            <a href="{{ route('admin.orders.documents.preview', [$order, $gambarDocument]) }}" target="_blank" class="inline-flex w-max items-center gap-2 rounded-lg bg-blue-500 px-3 py-1.5 text-[10px] font-semibold text-white transition hover:bg-blue-600">
                                                <i data-lucide="image" class="h-3 w-3"></i>
                                                <span>Gambar Teknik</span>
                                            </a>
                                        @endif
                                        @if ($order->scopeOfWork)
                                            <a href="{{ route('admin.orders.scope-of-work.pdf', [$order, $order->scopeOfWork]) }}" target="_blank" class="inline-flex w-max items-center gap-2 rounded-lg bg-emerald-500 px-3 py-1.5 text-[10px] font-semibold text-white transition hover:bg-emerald-600">
                                                <i data-lucide="file-badge" class="h-3 w-3"></i>
                                                <span>Scope of Work</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-slate-800">{{ $order->unit_kerja }}</div>
                                    <div class="mt-1 text-[9px] text-slate-400">{{ $order->seksi }}</div>
                                    <div class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[9px] font-semibold text-blue-700">{{ $order->catatan ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <input type="hidden" class="workshop-order-key" value="{{ $order->getRouteKey() }}">
                                    <div class="space-y-2">
                                        <div class="relative">
                                            <select name="konfirmasi_anggaran" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 pr-8 text-[10px] text-slate-800 shadow-sm" data-field="konfirmasi_anggaran">
                                                <option value="">Pilih Status Konfirmasi</option>
                                                @foreach ($konfirmasiOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(($workshop?->konfirmasi_anggaran ?? '') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="save-indicator absolute right-2 top-2 hidden text-[9px] text-slate-400">...</div>
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <textarea name="keterangan_konfirmasi" class="note-textarea h-10 flex-1 resize-none rounded-md border border-slate-300 px-2 py-1 text-[10px] text-slate-800" placeholder="Keterangan konfirmasi...">{{ $workshop?->keterangan_konfirmasi }}</textarea>
                                            <button type="button" class="save-note-btn inline-flex h-7 w-7 items-center justify-center rounded-md bg-indigo-600 text-white shadow-sm" data-field="keterangan_konfirmasi">
                                                <i data-lucide="save" class="h-3 w-3"></i>
                                            </button>
                                        </div>

                                        @if ($showEkorin)
                                            <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5 text-[9px] text-slate-700 shadow-sm">
                                                <div class="mb-2 font-semibold text-slate-800">E-Korin</div>
                                                <div class="space-y-2">
                                                    <input type="text" name="nomor_e_korin" value="{{ $workshop?->nomor_e_korin }}" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-[10px]" placeholder="Nomor E-Korin">
                                                    <select name="status_anggaran" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 text-[10px] text-slate-800 shadow-sm" data-field="status_anggaran">
                                                        <option value="">Pilih status anggaran</option>
                                                        @foreach ($statusAnggaranOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($workshop?->status_anggaran ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="status_e_korin" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 text-[10px] text-slate-800 shadow-sm" data-field="status_e_korin">
                                                        <option value="">Pilih status E-Korin</option>
                                                        @foreach ($eKorinStatusOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($workshop?->status_e_korin ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="save-note-btn inline-flex h-7 items-center justify-center rounded-md bg-slate-700 px-3 text-[10px] font-semibold text-white shadow-sm" data-field="nomor_e_korin">Simpan No. E-Korin</button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($showMaterial)
                                        <div class="space-y-2">
                                            <div class="relative">
                                                <select name="status_material" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 pr-8 text-[10px] text-slate-800 shadow-sm" data-field="status_material">
                                                    <option value="">Pilih status material</option>
                                                    @foreach ($materialOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($workshop?->status_material ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="save-indicator absolute right-2 top-2 hidden text-[9px] text-slate-400">...</div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <textarea name="keterangan_material" class="note-textarea h-10 flex-1 resize-none rounded-md border border-slate-300 px-2 py-1 text-[10px] text-slate-800" placeholder="Catatan material...">{{ $workshop?->keterangan_material }}</textarea>
                                                <button type="button" class="save-note-btn inline-flex h-7 w-7 items-center justify-center rounded-md bg-sky-600 text-white shadow-sm" data-field="keterangan_material">
                                                    <i data-lucide="save" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="italic text-slate-400">—</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($showProgress)
                                        <div class="space-y-2">
                                            <div class="relative">
                                                <select name="progress_status" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 pr-8 text-[10px] text-slate-800 shadow-sm" data-field="progress_status">
                                                    <option value="">Pilih progress</option>
                                                    @foreach ($progressOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($workshop?->progress_status ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="save-indicator absolute right-2 top-2 hidden text-[9px] text-slate-400">...</div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <textarea name="keterangan_progress" class="note-textarea h-10 flex-1 resize-none rounded-md border border-slate-300 px-2 py-1 text-[10px] text-slate-800" placeholder="Catatan progress...">{{ $workshop?->keterangan_progress }}</textarea>
                                                <button type="button" class="save-note-btn inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm" data-field="keterangan_progress">
                                                    <i data-lucide="save" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="italic text-slate-400">—</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-[10px] text-slate-700 shadow-sm">
                                        <div class="font-semibold text-slate-800">{{ $workshop?->catatan ?: ($order->catatan ?: '-') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">Tidak ada order bengkel untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="flex items-center justify-between border-t border-slate-200 px-4 py-4">
                    <div class="text-[11px] text-slate-500">Menampilkan <strong>{{ $orders->firstItem() ?: 0 }}</strong> - <strong>{{ $orders->lastItem() ?: 0 }}</strong> dari <strong>{{ $orders->total() }}</strong></div>
                    <div>{{ $orders->links() }}</div>
                </div>
            @endif
        </section>
    </div>

    <div id="workshopFlowOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>
    <div id="workshopFlowModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Alur Bengkel</div>
                    <h2 id="workshopFlowTitle" class="mt-1 text-lg font-bold text-slate-900">Order</h2>
                    <p id="workshopFlowSummary" class="mt-1 text-[11px] font-semibold text-slate-500">-</p>
                </div>
                <button type="button" data-close-workshop-flow class="text-2xl leading-none text-slate-400 transition hover:text-slate-700">&times;</button>
            </div>

            <div id="workshopFlowChecklist" class="mt-4 space-y-1.5 text-[11px]"></div>

            <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2.5">
                <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-blue-500">Next Step</div>
                <div id="workshopFlowNext" class="mt-1 text-[11px] font-semibold leading-5 text-slate-700">-</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const swal = window.Swal;
            const updateUrlTemplate = @json(route('admin.orders.workshop.update', '__ORDER__'));
            const workshopFlowOverlay = document.getElementById('workshopFlowOverlay');
            const workshopFlowModal = document.getElementById('workshopFlowModal');

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const openWorkshopFlow = () => {
                workshopFlowOverlay?.classList.remove('hidden');
                workshopFlowModal?.classList.remove('hidden');
                workshopFlowModal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeWorkshopFlow = () => {
                workshopFlowOverlay?.classList.add('hidden');
                workshopFlowModal?.classList.add('hidden');
                workshopFlowModal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const showToast = (message, icon = 'success') => {
                if (swal) {
                    swal.fire({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        icon,
                        title: message,
                    });
                    return;
                }

                alert(message);
            };

            const setIndicator = (element, visible) => {
                const indicator = element.closest('.relative')?.querySelector('.save-indicator');
                if (indicator) indicator.classList.toggle('hidden', !visible);
            };

            const setRowDisabled = (element, disabled) => {
                const row = element.closest('tr');
                row?.querySelectorAll('select, textarea, button, input[type="text"]').forEach((field) => {
                    field.disabled = disabled;
                });
            };

            const buildUrl = (orderKey) => updateUrlTemplate.replace('__ORDER__', encodeURIComponent(orderKey));

            const sendPatch = async (url, payload, indicatorElement = null) => {
                if (indicatorElement) {
                    setIndicator(indicatorElement, true);
                    setRowDisabled(indicatorElement, true);
                }

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                        credentials: 'same-origin',
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data?.error || data?.message || 'Gagal menyimpan data order bengkel.');
                    }

                    showToast(data?.message || 'Status order bengkel berhasil diperbarui.');
                    return data;
                } catch (error) {
                    showToast(error.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                    return null;
                } finally {
                    if (indicatorElement) {
                        setIndicator(indicatorElement, false);
                        setRowDisabled(indicatorElement, false);
                    }
                }
            };

            document.querySelectorAll('.auto-save-select').forEach((select) => {
                select.addEventListener('change', async () => {
                    const orderKey = select.closest('tr')?.querySelector('.workshop-order-key')?.value;
                    if (!orderKey) return;

                    await sendPatch(buildUrl(orderKey), {
                        [select.dataset.field || select.name]: select.value,
                    }, select);

                    if (select.name === 'konfirmasi_anggaran') {
                        setTimeout(() => window.location.reload(), 500);
                    }
                });
            });

            document.querySelectorAll('.save-note-btn').forEach((button) => {
                button.addEventListener('click', async () => {
                    const row = button.closest('tr');
                    const orderKey = row?.querySelector('.workshop-order-key')?.value;
                    const field = button.dataset.field;
                    if (!row || !orderKey || !field) return;

                    const source = row.querySelector(`[name="${field}"]`);
                    if (!source) return;

                    await sendPatch(buildUrl(orderKey), {
                        [field]: source.value,
                    }, button);
                });
            });

            document.querySelectorAll('.workshop-flow-trigger').forEach((button) => {
                button.addEventListener('click', () => {
                    const checklist = JSON.parse(button.dataset.checklist || '[]');
                    document.getElementById('workshopFlowTitle').textContent = button.dataset.title || 'Order';
                    document.getElementById('workshopFlowSummary').textContent = button.dataset.summary || '-';
                    document.getElementById('workshopFlowNext').textContent = button.dataset.next || '-';
                    document.getElementById('workshopFlowChecklist').innerHTML = checklist.map((item) => `
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <div class="min-w-0">
                                <div class="font-medium text-slate-700">${escapeHtml(item.label || '-')}</div>
                                <div class="mt-0.5 truncate text-[10px] text-slate-400">${escapeHtml(item.value || '-')}</div>
                            </div>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold ${item.ready ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'}">${item.ready ? 'OK' : 'Belum'}</span>
                        </div>
                    `).join('');
                    openWorkshopFlow();
                });
            });

            workshopFlowOverlay?.addEventListener('click', closeWorkshopFlow);
            document.querySelectorAll('[data-close-workshop-flow]').forEach((button) => {
                button.addEventListener('click', closeWorkshopFlow);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeWorkshopFlow();
                }
            });
        });
    </script>
</x-layouts.admin>
