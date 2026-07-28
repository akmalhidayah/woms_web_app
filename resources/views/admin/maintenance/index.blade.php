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
            'logs' => 'Log Laravel',
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

    <script>
        window.maintenanceScanner = (config) => ({
            quickSubmitting: false,
            deepModalOpen: false,
            deepRunning: false,
            requestActive: false,
            cancelRequested: false,
            scanId: null,
            currentStep: '',
            progress: 0,
            findingCount: 0,
            progressMessage: '',
            errorMessage: '',

            async request(url, payload = {}) {
                this.requestActive = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrf,
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json();

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Pemeriksaan tidak dapat dilanjutkan.');
                    }

                    return data;
                } finally {
                    this.requestActive = false;
                }
            },

            async startDeepScan() {
                this.deepRunning = true;
                this.cancelRequested = false;
                this.errorMessage = '';
                this.progress = 0;
                this.findingCount = 0;

                try {
                    const started = await this.request(config.startUrl);
                    this.scanId = started.scan_id;
                    this.currentStep = started.current_step;
                    this.progress = started.progress;
                    this.progressMessage = started.message;

                    while (!this.cancelRequested) {
                        const result = await this.request(config.stepUrl, {
                            scan_id: this.scanId,
                            step: this.currentStep,
                        });
                        this.progress = result.progress;
                        this.findingCount = result.finding_count;
                        this.progressMessage = result.message;

                        if (result.finished) {
                            const finalized = await this.request(config.finalizeUrl, {
                                scan_id: this.scanId,
                            });
                            this.progress = 100;
                            this.progressMessage = 'Pemeriksaan mendalam berhasil diselesaikan.';
                            window.location.assign(finalized.redirect_url);
                            return;
                        }

                        this.currentStep = result.next_step;
                        await new Promise((resolve) => window.setTimeout(resolve, 50));
                    }
                } catch (error) {
                    this.errorMessage = error.message || 'Pemeriksaan mendalam tidak dapat diselesaikan. Silakan periksa log aplikasi.';
                    if (this.scanId) {
                        try {
                            await this.request(config.cancelUrl, { scan_id: this.scanId });
                        } catch (_) {
                            // Lock tetap mempunyai TTL apabila koneksi ke server terputus.
                        }
                    }
                    this.deepRunning = false;
                }
            },

            async cancelDeepScan() {
                if (!this.scanId || this.requestActive) {
                    return;
                }

                this.cancelRequested = true;
                try {
                    await this.request(config.cancelUrl, { scan_id: this.scanId });
                    this.deepRunning = false;
                    this.deepModalOpen = false;
                    this.scanId = null;
                } catch (error) {
                    this.errorMessage = error.message || 'Pemeriksaan tidak dapat dibatalkan.';
                    this.cancelRequested = false;
                }
            },
        });
    </script>

    <div
        data-maintenance-page
        x-data="maintenanceScanner({
            csrf: @js(csrf_token()),
            startUrl: @js(route('admin.maintenance.scan.deep.start')),
            stepUrl: @js(route('admin.maintenance.scan.deep.step')),
            finalizeUrl: @js(route('admin.maintenance.scan.deep.finalize')),
            cancelUrl: @js(route('admin.maintenance.scan.deep.cancel')),
        })"
        class="space-y-4"
    >
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
                    <form method="POST" action="{{ route('admin.maintenance.scan.quick') }}" @submit="quickSubmitting = true">
                        @csrf
                        <button type="submit" :disabled="quickSubmitting || deepRunning" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i data-lucide="refresh-cw" class="h-4 w-4" :class="{ 'animate-spin': quickSubmitting }"></i>
                            <span x-text="quickSubmitting ? 'Memeriksa...' : 'Periksa Ulang'">Periksa Ulang</span>
                        </button>
                    </form>
                    <button type="button" @click="deepModalOpen = true" :disabled="quickSubmitting || deepRunning" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                            <i data-lucide="scan-search" class="h-4 w-4"></i>
                            Pemeriksaan Mendalam
                    </button>
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 p-2" aria-label="Tab Maintenance">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.maintenance.index', ['tab' => $key]) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold transition {{ $activeTab === $key ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            @if ($activeTab === 'logs')
                <div class="bg-slate-950">
                    <div class="flex flex-col gap-3 border-b border-slate-800 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2 text-sm font-bold text-white">
                                <i data-lucide="square-terminal" class="h-4 w-4 text-emerald-400"></i>
                                10 Log Laravel Terbaru
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Diambil dari ekor log saat halaman dimuat. Data sensitif dan path server disensor.</p>
                        </div>
                        <a
                            href="{{ route('admin.maintenance.index', ['tab' => 'logs', 'logs' => now()->timestamp]) }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
                        >
                            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                            Muat Log Terbaru
                        </a>
                    </div>

                    <div data-maintenance-log-panel class="max-h-[32rem] overflow-auto">
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
                </div>
            @elseif (! $snapshot)
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

        <div
            x-cloak
            x-show="deepModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6"
            @keydown.escape.window="if (!deepRunning) deepModalOpen = false"
        >
            <div x-show="deepModalOpen" x-transition class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-bold text-slate-900">Pemeriksaan Mendalam</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Pemeriksaan ini mengecek konsistensi dokumen dan keberadaan file secara bertahap. Proses dapat memerlukan beberapa waktu.
                    </p>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <template x-if="deepRunning || progress > 0">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700" x-text="progressMessage"></span>
                                <span class="font-bold text-blue-600" x-text="`${progress}%`"></span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-blue-600 transition-all duration-300" :style="`width: ${progress}%`"></div>
                            </div>
                            <p class="text-xs text-slate-500">
                                Temuan sementara: <strong x-text="findingCount"></strong>
                            </p>
                        </div>
                    </template>

                    <div x-show="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="errorMessage"></div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="deepRunning ? cancelDeepScan() : deepModalOpen = false"
                        :disabled="requestActive"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        x-text="deepRunning ? 'Batalkan Pemeriksaan' : 'Batal'"
                    >Batal</button>
                    <button
                        type="button"
                        @click="startDeepScan()"
                        :disabled="deepRunning || requestActive"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <i data-lucide="scan-search" class="h-4 w-4" :class="{ 'animate-pulse': deepRunning }"></i>
                        <span x-text="deepRunning ? 'Pemeriksaan sedang berjalan...' : 'Mulai Pemeriksaan'">Mulai Pemeriksaan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
