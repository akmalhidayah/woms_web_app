<div class="mt-3 space-y-3 lg:hidden">
    @forelse ($rows as $row)
        @php
            $photoTitle = $row['_work']['title'];
            $photoDate = collect([$row['_date_display'], $row['_time_display']])->filter()->join(' · ');
            $photoPic = collect($row['_pic_names'])->filter()->join(', ');
            $inputByMeta = $row['_input_by'] ? collect([
                $row['_input_by']['position'],
                $row['_input_by']['regu'] !== '' ? 'Regu '.$row['_input_by']['regu'] : '',
            ])->filter()->join(' · ') : '';
        @endphp
        <article class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
            <span class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-blue-600 via-cyan-400 to-transparent" aria-hidden="true"></span>
            <div class="flex items-start gap-3">
                @if ($row['_photo_thumbnail_url'] && $row['_photo_preview_url'])
                    <button
                        type="button"
                        class="group relative inline-flex h-[5.5rem] w-[6.5rem] shrink-0 cursor-zoom-in items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 shadow-sm ring-1 ring-slate-200 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        aria-label="Lihat foto {{ $photoTitle }}"
                        x-on:click="openPhoto(@js($row['_photo_preview_url']), @js($photoTitle), @js($photoDate ?: '-'), @js($photoPic ?: '-'))"
                    >
                        <i data-lucide="image" class="h-6 w-6" aria-hidden="true"></i>
                        <img src="{{ $row['_photo_thumbnail_url'] }}" alt="Foto {{ $photoTitle }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-cover object-center" onerror="this.remove()">
                        <span class="pointer-events-none absolute bottom-1.5 right-1.5 inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-950/55 text-white" aria-hidden="true">
                            <i data-lucide="maximize-2" class="h-3.5 w-3.5"></i>
                        </span>
                    </button>
                @else
                    <span class="inline-flex h-[5.5rem] w-[6.5rem] shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 ring-1 ring-slate-200">
                        <i data-lucide="image-off" class="h-6 w-6" aria-hidden="true"></i>
                    </span>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="flex flex-col items-start gap-1">
                        @if ($row['_order'] !== '' && trim((string) ($row['DESC. ORDER'] ?? '')) !== '')
                            <p class="inline-flex items-center gap-1 font-mono text-[10px] font-bold text-blue-600">
                                <i data-lucide="hash" class="h-3 w-3" aria-hidden="true"></i>
                                {{ $row['_order'] }}
                            </p>
                        @endif
                        <p class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2 py-1 text-[9px] font-semibold tabular-nums text-slate-500 ring-1 ring-slate-100">
                            <i data-lucide="calendar-clock" class="h-3 w-3" aria-hidden="true"></i>
                            {{ $photoDate ?: '-' }}
                        </p>
                    </div>
                    <h2 class="mt-1 line-clamp-3 text-[15px] font-bold leading-snug text-slate-900">{{ $photoTitle }}</h2>

                    @if ($row['_work']['user'] !== '' || $row['_work']['unit'] !== '')
                        <div class="mt-1 space-y-0.5 text-[10px] leading-relaxed text-slate-500">
                            @if ($row['_work']['user'] !== '')
                                <p><span class="font-semibold text-slate-600">User:</span> {{ $row['_work']['user'] }}</p>
                            @endif
                            @if ($row['_work']['unit'] !== '')
                                <p><span class="font-semibold text-slate-600">Unit:</span> {{ $row['_work']['unit'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-3 rounded-2xl bg-gradient-to-br from-slate-50 to-blue-50/60 p-3.5 ring-1 ring-slate-100">
                <p class="inline-flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">
                    <i data-lucide="activity" class="h-3 w-3 text-blue-500" aria-hidden="true"></i>
                    Progress
                </p>
                <p class="mt-1.5 whitespace-pre-line text-xs font-medium leading-relaxed text-slate-700">{{ $row['_progress'] ?: '-' }}</p>

                @if ($row['_pic_profiles'] !== [])
                    <div class="mt-3 border-t border-slate-200/70 pt-2.5">
                        <p class="mb-1.5 inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                            <i data-lucide="users" class="h-3 w-3" aria-hidden="true"></i>
                            PIC
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($row['_pic_profiles'] as $profile)
                                <div class="inline-flex items-center gap-1.5 rounded-xl bg-white py-1 pl-1 pr-2.5 shadow-sm ring-1 ring-slate-200">
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
            </div>

            @if ($row['_input_by'] || $row['_attachment_url'])
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5">
                    @if ($row['_input_by'])
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="relative inline-flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-blue-100 to-indigo-100 text-[9px] font-black text-blue-700 ring-1 ring-blue-200">
                                {{ $row['_input_by']['initials'] }}
                                @if ($row['_input_by']['avatar_url'])
                                    <img src="{{ $row['_input_by']['avatar_url'] }}" alt="Avatar {{ $row['_input_by']['name'] }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain object-center p-0.5" onerror="this.remove()">
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Input By</p>
                                <p class="truncate text-[11px] font-semibold text-slate-800">{{ $row['_input_by']['name'] }}</p>
                                @if ($inputByMeta !== '')
                                    <p class="truncate text-[9px] text-slate-400">{{ $inputByMeta }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($row['_attachment_url'])
                        <a href="{{ $row['_attachment_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-50 px-3 py-2 text-[10px] font-semibold text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100">
                            <i data-lucide="paperclip" class="h-3 w-3" aria-hidden="true"></i>
                            Lampiran
                        </a>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-14 text-center shadow-sm">
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
        </div>
    @endforelse
</div>
