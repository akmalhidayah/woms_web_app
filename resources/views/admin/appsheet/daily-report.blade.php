<x-layouts.admin title="Laporan Harian">
    <div
        class="min-w-0 space-y-5"
        x-data="{
            previewImageUrl: '',
            previewTitle: '',
            previewDate: '',
            previewPic: '',
            openPhoto(url, title, date, pic) {
                this.previewImageUrl = url;
                this.previewTitle = title;
                this.previewDate = date;
                this.previewPic = pic;
            },
            closePhoto() {
                this.previewImageUrl = '';
                this.previewTitle = '';
                this.previewDate = '';
                this.previewPic = '';
            },
        }"
        x-on:keydown.escape.window="closePhoto()"
    >
        <section class="overflow-hidden rounded-[1.35rem] border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-cyan-50 px-5 py-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200/70">
                    <i data-lucide="clipboard-list" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-[1.3rem] font-bold leading-tight tracking-tight text-slate-900">Laporan Harian</h1>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">Pantau aktivitas dan progres pekerjaan harian Workshop.</p>
                </div>
                @include('admin.appsheet.partials.google-connection', ['googleReturnTo' => 'daily-report'])
            </div>
        </section>

        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter Laporan Harian">
            <div class="mb-3 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i data-lucide="sliders-horizontal" class="h-4 w-4 text-blue-600" aria-hidden="true"></i>
                Filter laporan
            </div>
            <form method="GET" action="{{ route('admin.daily-report.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(230px,2fr)_minmax(150px,1fr)_minmax(105px,0.65fr)_minmax(145px,0.9fr)_auto] xl:items-end">
                <div>
                    <label for="daily-report-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="daily-report-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="Nomor order, pekerjaan, progress, PIC..." autocomplete="off" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="daily-report-pic" class="mb-1.5 block text-[11px] font-semibold text-slate-600">PIC</label>
                    <select id="daily-report-pic" name="pic" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua PIC</option>
                        @foreach ($picOptions as $pic)
                            <option value="{{ $pic }}" @selected($filters['pic'] === $pic)>{{ $pic }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="daily-report-year" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tahun</label>
                    <select id="daily-report-year" name="year" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua Tahun</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($filters['year'] === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="daily-report-date" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal</label>
                    <input id="daily-report-date" name="date" value="{{ $filters['date'] }}" type="date" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div class="flex h-10 items-center gap-2 sm:col-span-2 xl:col-span-1">
                    <button type="submit" class="inline-flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <i data-lucide="search" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.daily-report.index') }}" class="inline-flex h-10 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-xl px-3 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-blue-600">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5 sm:px-5">
                <p class="text-xs font-bold text-slate-800">Aktivitas pekerjaan harian</p>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-semibold text-slate-600">
                    <i data-lucide="arrow-down-narrow-wide" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    Terbaru lebih dahulu
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1040px] text-xs">
                    <caption class="sr-only">Laporan Harian Workshop</caption>
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left font-semibold">Foto</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Pekerjaan</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Progress</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left font-semibold">Input By</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left font-semibold">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $photoTitle = $row['_work']['title'];
                                $photoDate = collect([$row['_date_display'], $row['_time_display']])->filter()->join(' · ');
                                $photoPic = collect($row['_pic_names'])->filter()->join(', ');
                            @endphp
                            <tr class="align-middle text-slate-700 transition-colors hover:bg-blue-50/40">
                                <td class="px-5 py-3.5 align-top">
                                    @if ($row['_photo_thumbnail_url'] && $row['_photo_preview_url'])
                                        <button
                                            type="button"
                                            class="group relative inline-flex h-20 w-24 cursor-zoom-in items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 shadow-sm ring-1 ring-slate-200 transition hover:ring-2 hover:ring-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                            aria-label="Lihat foto {{ $photoTitle }}"
                                            title="Klik untuk melihat foto"
                                            x-on:click="openPhoto(@js($row['_photo_preview_url']), @js($photoTitle), @js($photoDate ?: '-'), @js($photoPic ?: '-'))"
                                        >
                                            <i data-lucide="image" class="h-6 w-6" aria-hidden="true"></i>
                                            <img src="{{ $row['_photo_thumbnail_url'] }}" alt="Foto {{ $photoTitle }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-cover object-center transition duration-200 group-hover:scale-105" onerror="this.remove()">
                                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-slate-950/30 text-white opacity-0 transition group-hover:opacity-100" aria-hidden="true">
                                                <i data-lucide="maximize-2" class="h-5 w-5"></i>
                                            </span>
                                        </button>
                                    @else
                                        <span class="inline-flex h-20 w-24 items-center justify-center rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 ring-1 ring-slate-200">
                                            <i data-lucide="image-off" class="h-6 w-6" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="min-w-72 max-w-sm px-4 py-3.5 align-top">
                                    <div class="space-y-1">
                                        @if ($row['_order'] !== '' && trim((string) ($row['DESC. ORDER'] ?? '')) !== '')
                                            <p class="inline-flex items-center gap-1 font-mono text-[10px] font-bold text-blue-600">
                                                <i data-lucide="hash" class="h-3 w-3" aria-hidden="true"></i>
                                                {{ $row['_order'] }}
                                            </p>
                                        @endif
                                        <p class="line-clamp-3 whitespace-pre-line text-sm font-bold leading-relaxed text-slate-900">{{ $row['_work']['title'] }}</p>
                                    </div>
                                    @if ($row['_work']['user'] !== '' || $row['_work']['unit'] !== '')
                                        <div class="mt-1.5 space-y-0.5 text-[10px] leading-relaxed text-slate-500">
                                            @if ($row['_work']['user'] !== '')
                                                <p><span class="font-semibold text-slate-600">User:</span> {{ $row['_work']['user'] }}</p>
                                            @endif
                                            @if ($row['_work']['unit'] !== '')
                                                <p><span class="font-semibold text-slate-600">Unit:</span> {{ $row['_work']['unit'] }}</p>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($row['_attachment_url'])
                                        <a href="{{ $row['_attachment_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100">
                                            <i data-lucide="paperclip" class="h-3 w-3" aria-hidden="true"></i>
                                            Lihat Lampiran
                                        </a>
                                    @endif
                                </td>
                                <td class="min-w-80 max-w-md px-5 py-3.5 align-top">
                                    <p class="line-clamp-4 whitespace-pre-line leading-relaxed text-slate-700">{{ $row['_progress'] ?: '-' }}</p>
                                    @if ($row['_pic_profiles'] !== [])
                                        <div class="mt-2.5 border-t border-slate-100 pt-2">
                                            <p class="mb-1.5 inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                                <i data-lucide="users" class="h-3 w-3" aria-hidden="true"></i>
                                                PIC
                                            </p>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @foreach ($row['_pic_profiles'] as $profile)
                                                    <div class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 py-1 pl-1 pr-2 ring-1 ring-slate-100">
                                                        <span class="relative inline-flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-md bg-gradient-to-br from-blue-100 to-indigo-100 text-[8px] font-black text-blue-700 ring-1 ring-blue-200">
                                                            {{ $profile['initials'] }}
                                                            @if ($profile['avatar_url'])
                                                                <img src="{{ $profile['avatar_url'] }}" alt="Avatar {{ $profile['name'] }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain object-center p-0.5" onerror="this.remove()">
                                                            @endif
                                                        </span>
                                                        <span class="text-[10px] font-semibold leading-none text-slate-600">{{ $profile['name'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="min-w-52 px-4 py-3.5 align-top">
                                    @if ($row['_input_by'])
                                        @php
                                            $inputByMeta = collect([
                                                $row['_input_by']['position'],
                                                $row['_input_by']['regu'] !== '' ? 'Regu '.$row['_input_by']['regu'] : '',
                                            ])->filter()->join(' · ');
                                        @endphp
                                        <div class="flex items-center gap-2.5">
                                            <span class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-[10px] font-black text-blue-700 ring-1 ring-blue-200">
                                                {{ $row['_input_by']['initials'] }}
                                                @if ($row['_input_by']['avatar_url'])
                                                    <img src="{{ $row['_input_by']['avatar_url'] }}" alt="Avatar {{ $row['_input_by']['name'] }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain object-center p-0.5" onerror="this.remove()">
                                                @endif
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug text-slate-900">{{ $row['_input_by']['name'] }}</p>
                                                @if ($inputByMeta !== '')
                                                    <p class="mt-1 text-[10px] leading-relaxed text-slate-400">{{ $inputByMeta }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 align-top">
                                    <p class="font-semibold tabular-nums text-slate-800">{{ $row['_date_display'] ?: '-' }}</p>
                                    @if ($row['_time_display'])
                                        <p class="mt-1 inline-flex items-center gap-1 text-[10px] tabular-nums text-slate-400">
                                            <i data-lucide="clock-3" class="h-3 w-3" aria-hidden="true"></i>
                                            {{ $row['_time_display'] }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="clipboard-x" class="h-6 w-6" aria-hidden="true"></i>
                                    </span>
                                    <p class="mt-4 text-sm font-semibold text-slate-700">Belum ada laporan harian yang sesuai dengan filter.</p>
                                    <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                                        @if ($sheetError || $googleConnectionError)
                                            Data Laporan Harian belum dapat dimuat. Coba perbarui koneksi atau muat ulang halaman.
                                        @elseif (! $googleConnected)
                                            Hubungkan Google untuk menampilkan Laporan Harian.
                                        @else
                                            Ubah filter atau tunggu laporan pekerjaan berikutnya tersedia.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())
                <div class="border-t border-slate-200 bg-slate-50/40 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </section>

        @include('admin.appsheet.partials.daily-report-image-preview-modal')
    </div>
</x-layouts.admin>
