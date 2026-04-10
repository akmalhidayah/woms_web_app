<x-layouts.admin title="Verifikasi Anggaran">
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

    <div class="space-y-4">
        <section class="rounded-[1.35rem] border border-emerald-100 px-4 py-3 shadow-sm" style="background: linear-gradient(135deg, #effcf6 0%, #f8fffb 45%, #e6f8ef 100%);">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-200">
                        <i data-lucide="wallet" class="h-3.5 w-3.5"></i>
                    </span>
                    <div>
                        <h1 class="text-[1.05rem] font-bold leading-none tracking-tight text-slate-900">Verifikasi Anggaran</h1>
                        <p class="mt-1 text-[10px] text-slate-500">Monitoring kesiapan dokumen, HPP, dana, kategori biaya, dan catatan verifikasi.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3 overflow-x-auto">
                <form method="GET" action="{{ route('admin.budget-verification.index') }}" class="flex min-w-[980px] items-center gap-2.5">
                    <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Cari nomor order / cost element..." class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none">
                    <select id="unit" name="unit" class="w-[210px] rounded-lg border border-slate-300 px-3 py-2 text-[10px] text-slate-700 focus:border-emerald-500 focus:outline-none">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit }}" @selected($selectedUnit === $unit)>{{ $unit }}</option>
                            @endforeach
                    </select>
                    <select id="kategori_item" name="kategori_item" class="w-[190px] rounded-lg border border-slate-300 px-3 py-2 text-[10px] text-slate-700 focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua Kategori</option>
                        <option value="spare part" @selected($selectedKategoriItem === 'spare part')>Spare Part</option>
                        <option value="jasa" @selected($selectedKategoriItem === 'jasa')>Jasa</option>
                    </select>

                    <div class="ml-auto flex items-center gap-2">
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
                        <col class="w-[18%]">
                        <col class="w-[17%]">
                        <col class="w-[17%]">
                        <col class="w-[12%]">
                        <col class="w-[24%]">
                    </colgroup>
                    <thead class="bg-slate-200/80 text-slate-700">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Nomor Order</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Dokumen</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Anggaran</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Kategori</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Cost Element</th>
                            <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($notifications as $notification)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <form id="budget-verification-form-{{ $notification['nomor_order'] }}" method="POST" action="{{ $notification['update_url'] }}" class="hidden">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_filter_search" value="{{ $search }}">
                                        <input type="hidden" name="_filter_unit" value="{{ $selectedUnit }}">
                                        <input type="hidden" name="_filter_kategori_item" value="{{ $selectedKategoriItem }}">
                                        <input type="hidden" name="_filter_page" value="{{ $notifications->currentPage() }}">
                                    </form>

                                    <div class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[13px] font-bold tracking-[0.04em] text-slate-900 shadow-sm">
                                        {{ $notification['nomor_order'] }}
                                    </div>
                                    <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5 text-left text-[8px] text-slate-600 shadow-sm">
                                        <div>
                                            <span class="block leading-tight text-blue-700">{{ $notification['seksi'] ?: '-' }}</span>
                                        </div>
                                        <div class="mt-1 border-t border-slate-200 pt-1">
                                            <span class="block leading-tight">{{ $notification['unit'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-[9px] font-semibold leading-5 text-slate-800">{{ $notification['nama_pekerjaan'] }}</div>
                                    <div class="mt-2.5 flex items-center gap-1.5">
                                        @foreach ([
                                            'abnormalitas' => ['label' => 'Abnormalitas', 'icon' => 'triangle-alert', 'class' => 'border-rose-200 bg-rose-50 text-rose-700'],
                                            'gambar_teknik' => ['label' => 'Gambar Teknik', 'icon' => 'image', 'class' => 'border-blue-200 bg-blue-50 text-blue-700'],
                                            'scope_of_work' => ['label' => 'Scope of Work', 'icon' => 'clipboard-list', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
                                            'hpp' => ['label' => 'HPP', 'icon' => 'file-text', 'class' => 'border-indigo-200 bg-indigo-50 text-indigo-700'],
                                        ] as $key => $meta)
                                            @php
                                                $document = $notification['dokumen'][$key];
                                            @endphp

                                            @if ($document['available'] && $document['url'])
                                                <a
                                                    href="{{ $document['url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="{{ $meta['label'] }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border transition {{ $meta['class'] }}"
                                                >
                                                    <i data-lucide="{{ $meta['icon'] }}" class="h-[12px] w-[12px]"></i>
                                                </a>
                                            @else
                                                <span
                                                    title="{{ $meta['label'] }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300"
                                                >
                                                    <i data-lucide="{{ $meta['icon'] }}" class="h-[12px] w-[12px]"></i>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="text-[10px] font-semibold text-slate-900">Rp {{ $formatRupiah($notification['nilai_hpp']) }}</div>
                                    <div class="mt-2.5">
                                        <select name="status_anggaran" form="budget-verification-form-{{ $notification['nomor_order'] }}" class="{{ $selectBaseClasses }}">
                                            <option value="">Pilih status Anggaran</option>
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($notification['status_anggaran'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1.5">
                                        <select name="kategori_item" form="budget-verification-form-{{ $notification['nomor_order'] }}" class="{{ $selectBaseClasses }}">
                                            <option value="">Pilih kategori item</option>
                                            @foreach ($kategoriItemOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($notification['kategori_item'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="kategori_biaya" form="budget-verification-form-{{ $notification['nomor_order'] }}" class="{{ $selectBaseClasses }}">
                                            <option value="">Pilih kategori biaya</option>
                                            @foreach ($kategoriBiayaOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($notification['kategori_biaya'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
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
                                    <div class="space-y-2">
                                        <textarea
                                            name="catatan"
                                            form="budget-verification-form-{{ $notification['nomor_order'] }}"
                                            rows="3"
                                            class="{{ $inputBaseClasses }} min-h-[78px] resize-none leading-4"
                                            placeholder="Tulis catatan verifikasi anggaran..."
                                        >{{ $notification['catatan'] }}</textarea>
                                        <div class="flex justify-end">
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
        });
    </script>
</x-layouts.admin>
