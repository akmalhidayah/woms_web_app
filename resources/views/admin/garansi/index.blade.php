<x-layouts.admin title="Garansi">
    <div class="space-y-4">
        <section class="rounded-[1.25rem] border border-emerald-100 px-4 py-3.5 shadow-sm" style="background: linear-gradient(135deg, #f2fff8 0%, #fbfffd 48%, #ecfff5 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm ring-1 ring-emerald-200">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                </span>
                <div>
                    <h1 class="text-[1.15rem] font-bold leading-none tracking-tight text-slate-900">Garansi</h1>
                    <p class="mt-1 text-[10px] text-slate-500">Kelola masa garansi berdasarkan LHPP dan Garansi.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3 overflow-x-auto">
                <form method="GET" action="{{ route('admin.garansi.index') }}" class="flex min-w-[720px] items-end gap-2">
                    <div class="w-[320px]">
                        <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500">Pencarian (Nomor Order)</label>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-[12px] w-[12px] -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Masukkan Nomor Order..." class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-[10px] font-semibold text-white transition hover:bg-emerald-700">
                            <i data-lucide="search" class="h-[12px] w-[12px]"></i>
                            Cari
                        </button>

                        <a href="{{ route('admin.garansi.index') }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-[12px] w-[12px]"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-[11px] text-slate-700">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Nomor Order</th>
                            <th class="px-4 py-3 text-left font-semibold">Mulai Garansi</th>
                            <th class="px-4 py-3 text-left font-semibold">Berakhir Garansi</th>
                            <th class="px-4 py-3 text-left font-semibold">Garansi</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-left font-semibold">Gambar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($garansiList as $g)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $g['order_number'] }}</div>
                                </td>

                                <td class="px-4 py-3 text-slate-700">
                                    @if (($g['ttd_date'] ?? '-') !== '-')
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="calendar-check-2" class="h-3 w-3 text-emerald-600"></i>
                                            {{ $g['ttd_date'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-slate-700">
                                    @if (array_key_exists('garansi_months', $g) && $g['garansi_months'] !== null)
                                        @if (($g['end_date'] ?? '-') !== '-')
                                            {{ $g['end_date'] }}
                                            <span class="text-[10px] text-slate-500">({{ $g['garansi_months'] }} {{ \Illuminate\Support\Str::plural('Bln', (int) $g['garansi_months']) }})</span>
                                        @else
                                            <span class="text-[10px] text-slate-500">
                                                {{ $g['garansi_months'] }} {{ \Illuminate\Support\Str::plural('Bulan', (int) $g['garansi_months']) }}
                                                <small class="text-slate-400">(Belum ada tanggal akhir)</small>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-slate-700">
                                    @if (array_key_exists('garansi_months', $g) && $g['garansi_months'] !== null)
                                        @if ($g['garansi_months'] === 0)
                                            <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700">
                                                <i data-lucide="ban" class="h-3 w-3"></i> 0 Bulan (Tanpa Garansi)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 text-[10px] text-indigo-700">
                                                <i data-lucide="clock-3" class="h-3 w-3"></i> {{ $g['garansi_months'] }} Bulan
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-[10px] text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @php
                                        $status = $g['status'] ?? null;
                                    @endphp

                                    @if ($status === 'Masih Berlaku')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">
                                            <i data-lucide="check-circle-2" class="h-3 w-3"></i> Masih Berlaku
                                        </span>
                                    @elseif ($status === 'Sudah Berakhir')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-800">
                                            <i data-lucide="x-circle" class="h-3 w-3"></i> Sudah Berakhir
                                        </span>
                                    @elseif ($status === 'Tidak Memiliki Garansi')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700">
                                            <i data-lucide="ban" class="h-3 w-3"></i> Tidak Memiliki Garansi
                                        </span>
                                    @elseif ($status === 'Belum Diatur')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] text-amber-800">
                                            <i data-lucide="clock-3" class="h-3 w-3"></i> Belum Diatur
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if (!empty($g['gambar']) && is_array($g['gambar']) && count($g['gambar']) > 0)
                                        <button
                                            type="button"
                                            onclick='openGaransiModal(@json($g["gambar"]))'
                                            class="inline-flex items-center gap-2 text-[11px] font-medium text-sky-600 hover:text-sky-700"
                                            title="Lihat gambar pekerjaan">
                                            <i data-lucide="images" class="h-4 w-4"></i>
                                            <span>Lihat</span>
                                            <span class="text-[10px] text-slate-500">({{ count($g['gambar']) }})</span>
                                        </button>
                                    @else
                                        <span class="text-[10px] text-slate-400">Belum ada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada data garansi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                @forelse ($garansiList as $g)
                    <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $g['order_number'] }}</div>
                            </div>

                            <div class="text-right">
                                @php
                                    $status = $g['status'] ?? null;
                                @endphp
                                @if ($status === 'Masih Berlaku')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-800">
                                        <i data-lucide="check-circle-2" class="h-3 w-3"></i> Masih
                                    </span>
                                @elseif ($status === 'Sudah Berakhir')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-800">
                                        <i data-lucide="x-circle" class="h-3 w-3"></i> Berakhir
                                    </span>
                                @elseif ($status === 'Tidak Memiliki Garansi')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700">
                                        <i data-lucide="ban" class="h-3 w-3"></i> Tanpa
                                    </span>
                                @elseif ($status === 'Belum Diatur')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] text-amber-800">
                                        <i data-lucide="clock-3" class="h-3 w-3"></i> Belum
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-slate-700">
                            <div>
                                <div class="text-[10px] text-slate-500">Mulai</div>
                                <div class="mt-1">{{ $g['ttd_date'] ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-500">Berakhir</div>
                                @if (array_key_exists('garansi_months', $g) && $g['garansi_months'] !== null)
                                    <div class="mt-1">
                                        {{ $g['end_date'] ?? '-' }}
                                        <div class="text-[10px] text-slate-500">({{ $g['garansi_months'] }} bln)</div>
                                    </div>
                                @else
                                    <div class="mt-1 text-slate-400">-</div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <div>
                                @if (!empty($g['gambar']) && is_array($g['gambar']) && count($g['gambar']) > 0)
                                    <button type="button" onclick='openGaransiModal(@json($g["gambar"]))' class="inline-flex items-center gap-2 text-[11px] text-sky-600 hover:text-sky-700">
                                        <i data-lucide="images" class="h-4 w-4"></i> Lihat ({{ count($g['gambar']) }})
                                    </button>
                                @else
                                    <span class="text-[10px] text-slate-400">Tidak ada gambar</span>
                                @endif
                            </div>

                            <div class="text-[10px] text-slate-500">
                                {{ $g['garansi_label'] ?? '' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-500">Tidak ada data garansi</div>
                @endforelse
            </div>

            @if (method_exists($garansiList, 'links') && $garansiList->hasPages())
                <div class="mt-4 border-t border-slate-200 px-4 py-4">
                    {{ $garansiList->links() }}
                </div>
            @endif
        </section>
    </div>

    <div id="garansiImageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
        <div id="garansiModalContent" class="relative w-full max-w-4xl rounded-lg bg-white p-4 shadow-lg">
            <button id="garansiCloseModalBtn" class="absolute right-3 top-3 text-2xl leading-none text-gray-600 hover:text-red-600" aria-label="Close">&times;</button>

            <div class="relative">
                <div id="garansiCarouselContainer" class="flex transition-transform duration-300 ease-in-out"></div>

                <button id="garansiPrevBtn" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-gray-800/60 p-2 text-white hover:bg-gray-800/90" aria-label="Previous">&lt;</button>
                <button id="garansiNextBtn" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-gray-800/60 p-2 text-white hover:bg-gray-800/90" aria-label="Next">&gt;</button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            let currentIndex = 0;
            let images = [];

            function el(id) { return document.getElementById(id); }

            window.openGaransiModal = function(imgList) {
                const modal = el('garansiImageModal');
                const container = el('garansiCarouselContainer');

                images = Array.isArray(imgList) ? imgList : [imgList];
                container.innerHTML = '';
                currentIndex = 0;

                images.forEach((img) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex w-full flex-shrink-0 items-center justify-center';
                    wrapper.style.minWidth = '100%';

                    const imgEl = document.createElement('img');
                    imgEl.alt = 'Gambar pekerjaan';
                    imgEl.className = 'max-h-96 object-contain';
                    imgEl.src = img;

                    wrapper.appendChild(imgEl);
                    container.appendChild(wrapper);
                });

                updateCarousel();
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            window.closeGaransiModal = function() {
                const modal = el('garansiImageModal');
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            function updateCarousel() {
                const container = el('garansiCarouselContainer');
                if (!container) return;
                container.style.transform = `translateX(-${currentIndex * 100}%)`;
            }

            el('garansiPrevBtn')?.addEventListener('click', () => {
                if (!images.length) return;
                currentIndex = currentIndex === 0 ? images.length - 1 : currentIndex - 1;
                updateCarousel();
            });

            el('garansiNextBtn')?.addEventListener('click', () => {
                if (!images.length) return;
                currentIndex = currentIndex === images.length - 1 ? 0 : currentIndex + 1;
                updateCarousel();
            });

            el('garansiCloseModalBtn')?.addEventListener('click', () => window.closeGaransiModal());

            el('garansiImageModal')?.addEventListener('click', (event) => {
                const modalContent = el('garansiModalContent');
                if (modalContent && !modalContent.contains(event.target)) {
                    window.closeGaransiModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') window.closeGaransiModal();
            });
        })();
    </script>
</x-layouts.admin>
