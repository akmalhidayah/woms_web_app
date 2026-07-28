<x-layouts.admin title="Maintenance Sistem">
    @php
        $snapshot = $quickSnapshot ?? $deepSnapshot;
        $summary = $snapshot['summary'] ?? ['critical' => 0, 'warning' => 0, 'info' => 0, 'total' => 0];
        $quickCategories = $quickSnapshot['categories'] ?? [];
        $deepCategories = $deepSnapshot['categories'] ?? [];
        $tabs = [
            'summary' => 'Ringkasan',
            'approval' => 'Approval',
            'documents' => 'Dokumen',
            'files' => 'File & Storage',
            'users_structure' => 'User & Struktur',
            'queue_scheduler' => 'Queue & Scheduler',
        ];
        $findings = match ($activeTab) {
            'approval' => $quickCategories['approval'] ?? [],
            'documents' => $quickCategories['documents'] ?? [],
            'files' => $deepCategories['files'] ?? [],
            'users_structure' => $quickCategories['users_structure'] ?? [],
            'queue_scheduler' => $quickCategories['queue_scheduler'] ?? [],
            default => collect($quickCategories)->flatten(1)
                ->filter(fn (array $finding) => in_array($finding['severity'] ?? null, ['critical', 'warning'], true))
                ->values()
                ->all(),
        };
        $severityClasses = [
            'critical' => 'bg-rose-100 text-rose-700',
            'warning' => 'bg-amber-100 text-amber-700',
            'info' => 'bg-blue-100 text-blue-700',
        ];
    @endphp

    <div data-maintenance-page class="space-y-4">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <section class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 ring-1 ring-blue-200">
                        <i data-lucide="wrench" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">Maintenance Sistem</h1>
                        <p class="mt-1 text-sm text-slate-500">Pusat pemeriksaan kondisi aplikasi dan konsistensi proses WOMS.</p>
                        <p class="mt-2 text-xs text-slate-500">
                            Quick Scan: {{ isset($quickSnapshot['completed_at']) ? \Illuminate\Support\Carbon::parse($quickSnapshot['completed_at'])->format('d/m/Y H:i') : 'Belum pernah diperiksa' }}
                            <span class="mx-1">•</span>
                            Deep Scan: {{ isset($deepSnapshot['completed_at']) ? \Illuminate\Support\Carbon::parse($deepSnapshot['completed_at'])->format('d/m/Y H:i') : 'Belum pernah diperiksa' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <form method="POST" action="{{ route('admin.maintenance.scan.quick') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                            Periksa Ulang
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.maintenance.scan.deep') }}" onsubmit="return confirm('Jalankan pemeriksaan mendalam? Proses berjalan melalui antrean.');">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i data-lucide="scan-search" class="h-4 w-4"></i>
                            Pemeriksaan Mendalam
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Masalah Kritis', $summary['critical'] ?? 0, 'alert-octagon', 'border-rose-200 bg-rose-50 text-rose-700'],
                ['Perlu Diperiksa', $summary['warning'] ?? 0, 'triangle-alert', 'border-amber-200 bg-amber-50 text-amber-700'],
                ['Informasi', $summary['info'] ?? 0, 'info', 'border-blue-200 bg-blue-50 text-blue-700'],
                ['Pemeriksaan Terakhir', isset($snapshot['completed_at']) ? \Illuminate\Support\Carbon::parse($snapshot['completed_at'])->format('d/m/Y H:i') : 'Belum pernah diperiksa', 'clock-3', 'border-slate-200 bg-white text-slate-700'],
            ] as [$label, $value, $icon, $classes])
                <div class="rounded-2xl border p-4 shadow-sm {{ $classes }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider">{{ $label }}</span>
                        <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                    </div>
                    <div class="mt-3 text-xl font-bold">{{ $value }}</div>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-700 bg-slate-950 shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-800 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-bold text-white">
                        <i data-lucide="square-terminal" class="h-4 w-4 text-emerald-400"></i>
                        10 Log Laravel Terbaru
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Diambil dari ekor log saat halaman dimuat. Data sensitif dan path server disensor.</p>
                </div>
                <a
                    href="{{ route('admin.maintenance.index', ['tab' => $activeTab, 'logs' => now()->timestamp]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
                >
                    <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                    Muat Log Terbaru
                </a>
            </div>

            <div data-maintenance-log-panel class="max-h-80 overflow-auto">
                @forelse ($latestLogs as $log)
                    @php
                        $logTone = match ($log['level']) {
                            'emergency', 'alert', 'critical', 'error' => 'text-rose-300',
                            'warning' => 'text-amber-300',
                            'notice', 'info' => 'text-blue-300',
                            default => 'text-slate-300',
                        };
                    @endphp
                    <div class="grid gap-1 border-b border-slate-800/80 px-4 py-2.5 font-mono text-[11px] leading-5 last:border-b-0 md:grid-cols-[150px_85px_minmax(0,1fr)]">
                        <span class="whitespace-nowrap text-slate-500">{{ $log['timestamp'] }}</span>
                        <span class="font-bold uppercase {{ $logTone }}">{{ $log['level'] }}</span>
                        <span class="break-words text-slate-300">{{ $log['message'] }}</span>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-400">Belum ada file log Laravel yang dapat dibaca.</div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 p-2" aria-label="Tab Maintenance">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.maintenance.index', ['tab' => $key]) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold transition {{ $activeTab === $key ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            @if (! $snapshot)
                <div class="px-5 py-14 text-center">
                    <i data-lucide="clipboard-search" class="mx-auto h-10 w-10 text-slate-300"></i>
                    <h2 class="mt-4 font-semibold text-slate-900">Belum pernah diperiksa.</h2>
                    <p class="mt-1 text-sm text-slate-500">Jalankan Pemeriksaan Cepat untuk membuat hasil diagnosis pertama.</p>
                </div>
            @elseif ($activeTab === 'files' && ! $deepSnapshot)
                <div class="px-5 py-14 text-center text-sm text-slate-500">Pemeriksaan file belum dijalankan.</div>
            @elseif (empty($findings))
                <div class="px-5 py-14 text-center">
                    <i data-lucide="circle-check" class="mx-auto h-10 w-10 text-emerald-500"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Tidak ada temuan pada kategori ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="bg-slate-100 text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Level</th>
                                <th class="px-4 py-3">Modul</th>
                                <th class="px-4 py-3">Dokumen/Data</th>
                                <th class="px-4 py-3">Masalah</th>
                                <th class="px-4 py-3">Ditemukan</th>
                                <th class="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($findings as $finding)
                                <tr class="align-top">
                                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $severityClasses[$finding['severity']] ?? $severityClasses['info'] }}">{{ ucfirst($finding['severity']) }}</span></td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $finding['module'] }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $finding['reference'] ?: '-' }}</td>
                                    <td class="max-w-md px-4 py-3"><div class="font-semibold text-slate-800">{{ $finding['title'] }}</div><div class="mt-1 text-xs leading-5 text-slate-500">{{ $finding['description'] }}</div></td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ isset($finding['detected_at']) ? \Illuminate\Support\Carbon::parse($finding['detected_at'])->format('d/m/Y H:i') : '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            @if (! empty($finding['url']))
                                                <a href="{{ $finding['url'] }}" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">Lihat Dokumen</a>
                                            @endif
                                            @if (! empty($finding['secondary_url']))
                                                <a href="{{ $finding['secondary_url'] }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">Buka Struktur</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="text-xs text-slate-500">
            Status scan: <strong>{{ strtoupper($scanStatus['status'] ?? 'idle') }}</strong>.
            Halaman ini tidak memperbarui data secara otomatis; muat ulang untuk melihat hasil terbaru.
        </div>
    </div>
</x-layouts.admin>
