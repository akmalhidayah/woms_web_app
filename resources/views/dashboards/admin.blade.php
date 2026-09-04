<x-layouts.admin title="Dashboard Admin">
    <style>
        html:has(.dashboard-header) {
            overflow-y: auto !important;
        }

        body:has(.dashboard-header) {
            overflow-y: visible !important;
        }

        .admin-compact {
            border: 0 !important;
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
    </style>

    @if ($activeDashboard === 'jasa')
        @php
            $headerRp = static function ($value): string {
                $amount = is_numeric($value) ? (int) round((float) $value) : 0;

                return 'Rp. '.number_format($amount, 0, ',', '.');
            };
            $headerPeriod = $periodeKontrak ?? ['start' => null, 'end' => null];
            $contractPeriodLabel = collect([
                $headerPeriod['start']
                    ? strtoupper(\Carbon\Carbon::parse($headerPeriod['start'])->locale('id')->translatedFormat('M Y'))
                    : null,
                $headerPeriod['end']
                    ? strtoupper(\Carbon\Carbon::parse($headerPeriod['end'])->locale('id')->translatedFormat('M Y'))
                    : null,
            ])->filter()->join(' - ');
        @endphp
    @endif

    <div class="space-y-3">
        @if ($showActionSummaryBanner ?? false)
            <section
                x-data="{ visible: true }"
                x-show="visible"
                x-transition.opacity
                data-admin-action-summary-banner
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 shadow-sm"
            >
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-start gap-3 text-left"
                    @click="$dispatch('admin-action-center:open')"
                    aria-label="Buka daftar pekerjaan yang perlu ditindaklanjuti"
                >
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <i data-lucide="circle-alert" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-bold text-amber-950">
                            Ada {{ $adminActionSummaryCount }} pekerjaan yang perlu ditindaklanjuti.
                        </span>
                        @if (($adminActionSummary ?? []) !== [])
                            <span class="mt-1 block text-[11px] leading-4 text-amber-800">
                                {{ collect($adminActionSummary)->map(fn (array $item): string => $item['label'].': '.$item['count'])->join(' · ') }}
                            </span>
                        @endif
                        <span class="mt-1 block text-[10px] font-semibold text-amber-700">Klik untuk melihat Perlu Tindakan.</span>
                    </span>
                </button>
                <button
                    type="button"
                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-amber-700 transition hover:bg-amber-100"
                    @click="visible = false"
                    aria-label="Tutup ringkasan tindakan"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </section>
        @endif

        <header class="dashboard-header grid gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:sticky lg:top-[52px] lg:z-10 xl:grid-cols-[minmax(240px,1fr)_minmax(0,auto)] xl:items-stretch">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                </span>
                <form id="dashboardTypeForm" method="GET" action="{{ route('admin.dashboard') }}" class="relative inline-flex min-w-0 max-w-full items-center">
                    <label for="dashboardTypeSelector" class="sr-only">Pilih dashboard</label>
                    <select
                        id="dashboardTypeSelector"
                        name="dashboard"
                        class="min-h-14 w-auto max-w-full appearance-none rounded-xl border border-transparent bg-transparent py-2 pl-3 pr-11 text-3xl font-extrabold tracking-[0.08em] text-slate-900 outline-none transition hover:border-slate-200 focus:border-blue-300 focus:ring-2 focus:ring-blue-100 sm:text-4xl"
                        aria-label="Pilih dashboard"
                    >
                        <option value="jasa" @selected($activeDashboard === 'jasa')>DASHBOARD BIAYA JASA</option>
                        <option value="bengkel" @selected($activeDashboard === 'bengkel')>DASHBOARD PEKERJAAN BENGKEL</option>
                    </select>
                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500"></i>
                </form>
            </div>

            @if ($activeDashboard === 'jasa')
                <div id="dashboardJasaHeaderControls" class="grid min-w-0 gap-x-3 gap-y-2 sm:grid-cols-2 xl:grid-cols-[minmax(220px,320px)_110px_minmax(210px,auto)] xl:items-center">
                    <form id="dashboardGlobalFilter" method="GET" action="{{ route('admin.dashboard') }}" class="contents">
                        <label class="min-w-0">
                            <span class="block text-[8px] font-bold uppercase tracking-[0.14em] text-slate-500">Outline Agreement</span>
                            <select id="dashboardOutlineAgreement" name="oa_id" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-semibold text-slate-700">
                                @forelse ($dashboardOutlineAgreements ?? [] as $agreement)
                                    <option value="{{ $agreement->id }}" @selected((int) $selectedOutlineAgreementId === (int) $agreement->id)>
                                        {{ $agreement->nomor_oa }} — {{ $agreement->nama_kontrak }} — {{ \App\Models\OutlineAgreement::statusOptions()[$agreement->status] ?? ucfirst($agreement->status) }}
                                    </option>
                                @empty
                                    <option value="">Belum ada Outline Agreement</option>
                                @endforelse
                            </select>
                        </label>
                        <label class="min-w-0">
                            <span class="block text-[8px] font-bold uppercase tracking-[0.14em] text-slate-500">Tahun</span>
                            <select id="dashboardYear" name="year" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-semibold text-slate-700">
                                <option value="all" @selected($selectedDashboardYear === null)>Semua Tahun</option>
                                @foreach ($dashboardAvailableYears ?? [] as $year)
                                    <option value="{{ $year }}" @selected((int) $selectedDashboardYear === (int) $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </label>
                        <noscript><button type="submit">Terapkan</button></noscript>
                    </form>

                    <aside class="contract-budget-summary min-w-0 border-t-2 border-blue-600 pt-3 sm:col-span-2 xl:col-span-1 xl:border-l-2 xl:border-t-0 xl:py-1 xl:pl-4 xl:text-right">
                        <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-blue-700">Pagu Kontrak</div>
                        <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                            {{ $contractPeriodLabel !== '' ? $contractPeriodLabel : '-' }}
                        </div>
                        <div class="mt-1 break-words text-lg font-extrabold leading-6 text-slate-950 sm:text-xl">
                            {{ $headerRp($totalPaguKontrak) }}
                        </div>
                    </aside>
                </div>
            @else
                <form id="dashboardWorkshopFilter" method="GET" action="{{ route('admin.dashboard') }}" class="grid min-w-0 gap-2 sm:grid-cols-2 xl:grid-cols-[130px_170px] xl:items-center">
                    <input type="hidden" name="dashboard" value="bengkel">
                    <label class="min-w-0">
                        <span class="block text-[8px] font-bold uppercase tracking-[0.14em] text-slate-500">Tahun</span>
                        <select id="dashboardWorkshopYear" name="workshop_year" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px] font-semibold text-slate-700">
                            @foreach ($workshopDashboard['available_years'] as $year)
                                <option value="{{ $year }}" @selected($workshopDashboard['filters']['year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="min-w-0">
                        <span class="block text-[8px] font-bold uppercase tracking-[0.14em] text-slate-500">Bulan</span>
                        <select id="dashboardWorkshopMonth" name="workshop_month" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px] font-semibold text-slate-700">
                            <option value="all" @selected($workshopDashboard['filters']['month'] === null)>Semua Bulan</option>
                            @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $month => $label)
                                <option value="{{ $month }}" @selected($workshopDashboard['filters']['month'] === $month)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <noscript><button type="submit">Terapkan</button></noscript>
                </form>
            @endif
        </header>

        @if ($activeDashboard === 'bengkel')
            @include('dashboards.admin.sections.pekerjaan-bengkel')
        @else
            @include('dashboards.admin.sections.biaya-jasa')
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @if ($activeDashboard === 'bengkel')
        @include('dashboards.admin.scripts.pekerjaan-bengkel')
    @else
        @include('dashboards.admin.scripts.biaya-jasa')
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dashboardTypeSelector = document.getElementById('dashboardTypeSelector');
            const dashboardTypeForm = document.getElementById('dashboardTypeForm');
            const workshopFilterForm = document.getElementById('dashboardWorkshopFilter');

            dashboardTypeSelector?.addEventListener('change', () => dashboardTypeForm?.submit());
            workshopFilterForm?.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', () => workshopFilterForm.submit());
            });
        });
    </script>
</x-layouts.admin>
