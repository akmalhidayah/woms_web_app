<x-layouts.admin title="Verifikasi Anggaran">
    <style>
        .budget-index-filter {
            display: grid;
            gap: 0.5rem;
        }

        .budget-auto-save-container {
            position: relative;
        }

        .js-budget-auto-save[data-save-state="saving"] {
            border-color: #f59e0b;
            background-color: #fffbeb;
            opacity: 0.8;
        }

        .js-budget-auto-save[data-save-state="saved"] {
            border-color: #10b981;
            background-color: #ecfdf5;
        }

        .js-budget-auto-save[data-save-state="error"] {
            border-color: #f43f5e;
            background-color: #fff1f2;
        }

        @media (min-width: 760px) {
            .budget-index-filter {
                grid-template-columns: minmax(180px, 1.6fr) minmax(130px, 0.7fr) minmax(125px, 0.65fr) auto;
                align-items: center;
            }
        }
    </style>

    @if (session('status'))
        <div id="budget-verification-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    @if (session('error'))
        <div id="budget-verification-error-alert" data-message="{{ session('error') }}" class="hidden"></div>
    @endif

    @php
        $formatRupiah = function ($value): string {
            return number_format((float) $value, 0, ',', '.');
        };

        $selectBaseClasses = 'w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[9px] font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none';
        $inputBaseClasses = 'w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[9px] text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none';
    @endphp

    <div class="order-list-compact space-y-4">
        <section class="order-list-hero rounded-[1.35rem] border border-emerald-100 px-4 py-3 shadow-sm" style="background: linear-gradient(135deg, #effcf6 0%, #f8fffb 45%, #e6f8ef 100%);">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-200">
                        <i data-lucide="wallet" class="h-3.5 w-3.5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.05rem] font-bold leading-none tracking-tight text-slate-900">Verifikasi Anggaran</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="order-list-panel overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <form method="GET" action="{{ route('admin.budget-verification.index') }}" class="budget-index-filter">
                    <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / cost element..." class="min-w-0 w-full rounded-lg border border-slate-300 px-3 py-2 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none">
                    <select id="unit" name="unit" class="min-w-0 w-full rounded-lg border border-slate-300 px-3 py-2 text-[10px] text-slate-700 focus:border-emerald-500 focus:outline-none">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected($selectedUnit === $unit)>{{ $unit }}</option>
                            @endforeach
                    </select>
                    <select id="kategori_item" name="kategori_item" class="min-w-0 w-full rounded-lg border border-slate-300 px-3 py-2 text-[10px] text-slate-700 focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua Kategori</option>
                        <option value="spare part" @selected($selectedKategoriItem === 'spare part')>Spare Part</option>
                        <option value="jasa" @selected($selectedKategoriItem === 'jasa')>Jasa</option>
                    </select>

                    <div class="flex items-center justify-end gap-1.5">
                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700" title="Filter">
                            <i data-lucide="filter" class="h-[12px] w-[12px]"></i>
                        </button>
                        <a href="{{ route('admin.budget-verification.index') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50" title="Reset">
                            <i data-lucide="rotate-ccw" class="h-[12px] w-[12px]"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed divide-y divide-slate-200 text-[10px] text-slate-700">
                    <colgroup>
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                        <col class="w-[15%]">
                        <col class="w-[17%]">
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Nomor Order</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Detail Pekerjaan</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Anggaran</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kategori</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Cost Element</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($notifications as $notification)
                            @php
                                $rowClasses = $notification['is_executed']
                                    ? 'bg-emerald-50/70 hover:bg-emerald-100/60'
                                    : 'bg-white hover:bg-slate-50';
                                $executionBadgeClasses = $notification['is_executed']
                                    ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
                                    : 'border-slate-200 bg-white text-slate-500';
                            @endphp
                            <tr class="align-top transition {{ $rowClasses }}">
                                <td class="px-4 py-3">
                                    <form id="budget-verification-form-{{ $notification['nomor_order'] }}" method="POST" action="{{ $notification['update_url'] }}" class="hidden">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_filter_search" value="{{ $search }}">
                                        <input type="hidden" name="_filter_unit" value="{{ $selectedUnit }}">
                                        <input type="hidden" name="_filter_kategori_item" value="{{ $selectedKategoriItem }}">
                                        <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">
                                    </form>

                                    <div class="text-[12px] font-bold tracking-[0.01em] text-slate-900">
                                        {{ $notification['nomor_order'] }}
                                    </div>
                                    <div class="mt-0.5 text-[9px] font-medium text-blue-600">
                                        Notif: {{ $notification['notifikasi'] ?: '-' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-[10px] font-semibold leading-4 text-slate-800">{{ $notification['nama_pekerjaan'] }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[9px]">
                                        <span class="text-slate-500">Unit: <strong class="font-semibold text-slate-700">{{ $notification['unit'] }}</strong></span>
                                        <span class="text-slate-300">|</span>
                                        <span class="text-blue-500">Seksi: <strong class="font-semibold text-blue-700">{{ $notification['seksi'] ?: '-' }}</strong></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="text-[10px] font-semibold text-slate-900">Rp {{ $formatRupiah($notification['nilai_hpp']) }}</div>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[8px] font-semibold {{ $executionBadgeClasses }}">
                                            {{ $notification['execution_label'] }}
                                        </span>
                                    </div>
                                    <div class="budget-auto-save-container mt-2.5">
                                        <select
                                            name="status_anggaran"
                                            form="budget-verification-form-{{ $notification['nomor_order'] }}"
                                            class="{{ $selectBaseClasses }} js-budget-auto-save"
                                            data-field="status_anggaran"
                                            data-url="{{ $notification['update_url'] }}"
                                            data-order-number="{{ $notification['nomor_order'] }}"
                                            data-saved-value="{{ $notification['status_anggaran'] ?? '' }}"
                                        >
                                            <option value="">Pilih status Anggaran</option>
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($notification['status_anggaran'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="js-budget-auto-save-status hidden pt-0.5 text-[8px] font-medium" aria-live="polite"></div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="budget-auto-save-container">
                                            <select
                                                name="kategori_item"
                                                form="budget-verification-form-{{ $notification['nomor_order'] }}"
                                                class="{{ $selectBaseClasses }} js-budget-auto-save"
                                                data-field="kategori_item"
                                                data-url="{{ $notification['update_url'] }}"
                                                data-order-number="{{ $notification['nomor_order'] }}"
                                                data-saved-value="{{ $notification['kategori_item'] ?? '' }}"
                                            >
                                                <option value="">Pilih kategori item</option>
                                                @foreach ($kategoriItemOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($notification['kategori_item'] === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="js-budget-auto-save-status hidden pt-0.5 text-[8px] font-medium" aria-live="polite"></div>
                                        </div>
                                        <div class="budget-auto-save-container">
                                            <select
                                                name="kategori_biaya"
                                                form="budget-verification-form-{{ $notification['nomor_order'] }}"
                                                class="{{ $selectBaseClasses }} js-budget-auto-save"
                                                data-field="kategori_biaya"
                                                data-url="{{ $notification['update_url'] }}"
                                                data-order-number="{{ $notification['nomor_order'] }}"
                                                data-saved-value="{{ $notification['kategori_biaya'] ?? '' }}"
                                            >
                                                <option value="">Pilih kategori biaya</option>
                                                @foreach ($kategoriBiayaOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($notification['kategori_biaya'] === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="js-budget-auto-save-status hidden pt-0.5 text-[8px] font-medium" aria-live="polite"></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        name="cost_element"
                                        form="budget-verification-form-{{ $notification['nomor_order'] }}"
                                        value="{{ $notification['cost_element'] ?: '' }}"
                                        class="{{ $inputBaseClasses }} font-mono font-semibold"
                                        placeholder="Cost element"
                                    >
                                </td>

                                <td class="px-4 py-3">
                                    @php
                                        $mergedDocument = $notification['dokumen']['hpp_abnormalitas'];
                                    @endphp
                                    <div class="space-y-2">
                                        <textarea
                                            name="catatan"
                                            form="budget-verification-form-{{ $notification['nomor_order'] }}"
                                            rows="3"
                                            class="{{ $inputBaseClasses }} min-h-[78px] resize-none leading-4"
                                            placeholder="Tulis catatan verifikasi anggaran..."
                                        >{{ $notification['catatan'] }}</textarea>
                                        <div class="flex justify-end gap-1.5">
                                            @if ($mergedDocument['available'] && $mergedDocument['url'])
                                                <a
                                                    href="{{ $mergedDocument['url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100"
                                                    title="PDF HPP + Abnormalitas"
                                                    aria-label="PDF HPP + Abnormalitas"
                                                >
                                                    <i data-lucide="file-text" class="h-[12px] w-[12px]"></i>
                                                </a>
                                            @else
                                                <span
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400"
                                                    title="Dokumen Abnormalitas belum tersedia"
                                                    aria-label="Dokumen Abnormalitas belum tersedia"
                                                >
                                                    <i data-lucide="file-x" class="h-[12px] w-[12px]"></i>
                                                </span>
                                            @endif
                                            <button type="submit" form="budget-verification-form-{{ $notification['nomor_order'] }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[9px] font-semibold text-white transition hover:bg-emerald-700">
                                                <i data-lucide="save" class="h-[11px] w-[11px]"></i>
                                                Update
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-[11px] text-slate-500">Tidak ada data verifikasi anggaran untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($notifications->hasPages())
                <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3">
                    <div class="text-[10px] text-slate-500">
                        Menampilkan <strong>{{ $notifications->firstItem() ?: 0 }}</strong> - <strong>{{ $notifications->lastItem() ?: 0 }}</strong> dari <strong>{{ $notifications->total() }}</strong> data
                    </div>
                    <div>{{ $notifications->links() }}</div>
                </div>
            @endif
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const successAlert = document.getElementById('budget-verification-status-alert');
            const errorAlert = document.getElementById('budget-verification-error-alert');
            const requestControllers = new WeakMap();
            const statusTimers = new WeakMap();

            if (successAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: successAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            if (errorAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorAlert.dataset.message,
                });
            }

            const showAutoSaveError = (message) => {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Gagal menyimpan',
                        text: message,
                    });

                    return;
                }

                window.alert(message);
            };

            const setAutoSaveStatus = (select, state, message) => {
                const status = select.closest('.budget-auto-save-container')
                    ?.querySelector('.js-budget-auto-save-status');
                const previousTimer = statusTimers.get(select);

                if (previousTimer) {
                    window.clearTimeout(previousTimer);
                    statusTimers.delete(select);
                }

                select.dataset.saveState = state;

                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.remove('hidden', 'text-amber-600', 'text-emerald-600', 'text-rose-600');
                status.classList.add(state === 'saving'
                    ? 'text-amber-600'
                    : (state === 'saved' ? 'text-emerald-600' : 'text-rose-600'));

                if (state === 'saved') {
                    const timer = window.setTimeout(() => {
                        status.classList.add('hidden');
                        delete select.dataset.saveState;
                        statusTimers.delete(select);
                    }, 1200);
                    statusTimers.set(select, timer);
                }
            };

            document.querySelectorAll('.js-budget-auto-save').forEach((select) => {
                select.addEventListener('change', async () => {
                    const field = select.dataset.field ?? '';
                    const url = select.dataset.url ?? '';
                    const savedValue = select.dataset.savedValue ?? '';
                    const currentValue = select.value;

                    if (!field || !url || currentValue === savedValue) {
                        return;
                    }

                    const previousController = requestControllers.get(select);

                    if (previousController) {
                        previousController.abort();
                    }

                    const controller = new AbortController();
                    requestControllers.set(select, controller);
                    select.disabled = true;
                    setAutoSaveStatus(select, 'saving', 'Menyimpan...');

                    try {
                        const response = await fetch(url, {
                            method: 'PATCH',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document
                                    .querySelector('meta[name="csrf-token"]')
                                    ?.getAttribute('content') ?? '',
                            },
                            body: JSON.stringify({
                                [field]: currentValue,
                            }),
                            signal: controller.signal,
                        });
                        const responseData = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            let errorMessage = 'Gagal menyimpan perubahan. Silakan coba kembali.';

                            if (response.status === 422) {
                                errorMessage = responseData?.errors?.[field]?.[0]
                                    || responseData?.message
                                    || errorMessage;
                            } else if (response.status === 419) {
                                errorMessage = 'Sesi Anda telah berakhir. Silakan muat ulang halaman.';
                            } else if (response.status === 403) {
                                errorMessage = 'Anda tidak memiliki akses untuk memperbarui data ini.';
                            }

                            throw Object.assign(new Error(errorMessage), { responseHandled: true });
                        }

                        const returnedValue = responseData?.data?.[field];
                        select.dataset.savedValue = returnedValue ?? '';
                        select.value = returnedValue ?? '';
                        setAutoSaveStatus(select, 'saved', 'Tersimpan');
                    } catch (error) {
                        if (error?.name === 'AbortError') {
                            return;
                        }

                        select.value = select.dataset.savedValue ?? '';
                        setAutoSaveStatus(select, 'error', 'Gagal disimpan');
                        showAutoSaveError(error?.responseHandled
                            ? error.message
                            : 'Gagal menyimpan perubahan. Silakan coba kembali.');
                    } finally {
                        if (requestControllers.get(select) === controller) {
                            requestControllers.delete(select);
                            select.disabled = false;
                        }
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
