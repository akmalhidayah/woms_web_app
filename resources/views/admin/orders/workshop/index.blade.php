<x-layouts.admin title="Order Pekerjaan Bengkel">
    <div class="space-y-6">
        <section class="rounded-[1.5rem] border border-blue-100 px-6 py-5 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="factory" class="h-6 w-6"></i>
                </span>
                <div>
                    <h1 class="text-[2rem] font-bold leading-none tracking-tight text-slate-900">Order Pekerjaan Bengkel</h1>
                    <p class="mt-2 text-sm text-slate-500">Order yang diarahkan ke bengkel dari status workshop dan workshop + jasa.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <form method="GET" action="{{ route('admin.orders.workshop.index') }}" class="flex flex-col gap-2.5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="grid flex-1 gap-2.5 md:grid-cols-4 xl:grid-cols-[1.2fr_0.9fr_0.8fr_0.8fr]">
                        <div class="flex flex-col">
                            <label for="search" class="mb-1.5 text-[11px] font-semibold text-slate-700">Pencarian</label>
                            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor / pekerjaan / unit..." class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div class="flex flex-col">
                            <label for="progress" class="mb-1.5 text-[11px] font-semibold text-slate-700">Progress</label>
                            <select id="progress" name="progress" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Progress</option>
                                @foreach ($progressOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedProgress === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label for="regu" class="mb-1.5 text-[11px] font-semibold text-slate-700">Regu</label>
                            <select id="regu" name="regu" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Semua Regu</option>
                                @foreach ($reguOptions as $regu)
                                    <option value="{{ $regu }}" @selected($selectedRegu === $regu)>{{ $regu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label for="perPage" class="mb-1.5 text-[11px] font-semibold text-slate-700">Per Halaman</label>
                            <select id="perPage" name="perPage" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                                @foreach ([10, 25, 50] as $option)
                                    <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" title="Filter">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                        </button>
                        <a href="{{ route('admin.orders.workshop.index') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-[11px] text-slate-700 order-workshop-table">
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Nomor Order</th>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Deskripsi & Dokumen</th>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Unit / Seksi</th>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Konfirmasi Anggaran</th>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Status Material</th>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Progress Pekerjaan</th>
                            <th class="px-3 py-3 text-left text-[11px] font-semibold uppercase">Catatan</th>
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
                            @endphp
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-800">{{ $order->nomor_order }}</div>
                                    <div class="mt-1 text-[10px] text-slate-400">Tanggal: {{ optional($order->tanggal_order)->format('d-m-Y') ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-slate-800">{{ \Illuminate\Support\Str::limit($order->nama_pekerjaan, 180) }}</div>
                                    <div class="mt-2 flex flex-col gap-2">
                                        @if ($abnormalDocument)
                                            <a href="{{ route('admin.orders.documents.preview', [$order, $abnormalDocument]) }}" target="_blank" class="inline-flex w-max items-center gap-2 rounded-lg bg-red-500 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-red-600">
                                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                <span>Abnormalitas</span>
                                            </a>
                                        @endif
                                        @if ($gambarDocument)
                                            <a href="{{ route('admin.orders.documents.preview', [$order, $gambarDocument]) }}" target="_blank" class="inline-flex w-max items-center gap-2 rounded-lg bg-blue-500 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-blue-600">
                                                <i data-lucide="image" class="h-3.5 w-3.5"></i>
                                                <span>Gambar Teknik</span>
                                            </a>
                                        @endif
                                        @if ($order->scopeOfWork)
                                            <a href="{{ route('admin.orders.scope-of-work.pdf', [$order, $order->scopeOfWork]) }}" target="_blank" class="inline-flex w-max items-center gap-2 rounded-lg bg-emerald-500 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-emerald-600">
                                                <i data-lucide="file-badge" class="h-3.5 w-3.5"></i>
                                                <span>Scope of Work</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-slate-800">{{ $order->unit_kerja }}</div>
                                    <div class="mt-1 text-[10px] text-slate-400">{{ $order->seksi }}</div>
                                    <div class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700">{{ $order->catatan ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <input type="hidden" class="workshop-order-key" value="{{ $order->getRouteKey() }}">
                                    <div class="space-y-2">
                                        <div class="relative">
                                            <select name="konfirmasi_anggaran" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 pr-8 text-[11px] text-slate-800 shadow-sm" data-field="konfirmasi_anggaran">
                                                <option value="">Pilih Status Konfirmasi</option>
                                                @foreach ($konfirmasiOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(($workshop?->konfirmasi_anggaran ?? '') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="save-indicator absolute right-2 top-2 hidden text-[10px] text-slate-400">...</div>
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <textarea name="keterangan_konfirmasi" class="note-textarea h-10 flex-1 resize-none rounded-md border border-slate-300 px-2 py-1 text-[11px] text-slate-800" placeholder="Keterangan konfirmasi...">{{ $workshop?->keterangan_konfirmasi }}</textarea>
                                            <button type="button" class="save-note-btn inline-flex h-8 w-8 items-center justify-center rounded-md bg-indigo-600 text-white shadow-sm" data-field="keterangan_konfirmasi">
                                                <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </div>

                                        @if ($showEkorin)
                                            <div class="rounded-md border border-slate-200 bg-slate-50 p-2.5 text-[10px] text-slate-700 shadow-sm">
                                                <div class="mb-2 font-semibold text-slate-800">E-Korin</div>
                                                <div class="space-y-2">
                                                    <input type="text" name="nomor_e_korin" value="{{ $workshop?->nomor_e_korin }}" class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-[11px]" placeholder="Nomor E-Korin">
                                                    <select name="status_anggaran" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 text-[11px] text-slate-800 shadow-sm" data-field="status_anggaran">
                                                        <option value="">Pilih status anggaran</option>
                                                        @foreach ($statusAnggaranOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($workshop?->status_anggaran ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="status_e_korin" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 text-[11px] text-slate-800 shadow-sm" data-field="status_e_korin">
                                                        <option value="">Pilih status E-Korin</option>
                                                        @foreach ($eKorinStatusOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($workshop?->status_e_korin ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="save-note-btn inline-flex h-8 items-center justify-center rounded-md bg-slate-700 px-3 text-[11px] font-semibold text-white shadow-sm" data-field="nomor_e_korin">Simpan No. E-Korin</button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($showMaterial)
                                        <div class="space-y-2">
                                            <div class="relative">
                                                <select name="status_material" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 pr-8 text-[11px] text-slate-800 shadow-sm" data-field="status_material">
                                                    <option value="">Pilih status material</option>
                                                    @foreach ($materialOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($workshop?->status_material ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="save-indicator absolute right-2 top-2 hidden text-[10px] text-slate-400">...</div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <textarea name="keterangan_material" class="note-textarea h-10 flex-1 resize-none rounded-md border border-slate-300 px-2 py-1 text-[11px] text-slate-800" placeholder="Catatan material...">{{ $workshop?->keterangan_material }}</textarea>
                                                <button type="button" class="save-note-btn inline-flex h-8 w-8 items-center justify-center rounded-md bg-sky-600 text-white shadow-sm" data-field="keterangan_material">
                                                    <i data-lucide="save" class="h-3.5 w-3.5"></i>
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
                                                <select name="progress_status" class="auto-save-select block w-full rounded-md border border-slate-300 bg-white px-2.5 py-2 pr-8 text-[11px] text-slate-800 shadow-sm" data-field="progress_status">
                                                    <option value="">Pilih progress</option>
                                                    @foreach ($progressOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($workshop?->progress_status ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="save-indicator absolute right-2 top-2 hidden text-[10px] text-slate-400">...</div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <textarea name="keterangan_progress" class="note-textarea h-10 flex-1 resize-none rounded-md border border-slate-300 px-2 py-1 text-[11px] text-slate-800" placeholder="Catatan progress...">{{ $workshop?->keterangan_progress }}</textarea>
                                                <button type="button" class="save-note-btn inline-flex h-8 w-8 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm" data-field="keterangan_progress">
                                                    <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="italic text-slate-400">—</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-[11px] text-slate-700 shadow-sm">
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
                    <div class="text-xs text-slate-500">Menampilkan <strong>{{ $orders->firstItem() ?: 0 }}</strong> - <strong>{{ $orders->lastItem() ?: 0 }}</strong> dari <strong>{{ $orders->total() }}</strong></div>
                    <div>{{ $orders->links() }}</div>
                </div>
            @endif
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const swal = window.Swal;
            const updateUrlTemplate = @json(route('admin.orders.workshop.update', '__ORDER__'));

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
        });
    </script>
</x-layouts.admin>
