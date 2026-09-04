@php
    $reguStyles = [
        ['card' => 'bg-blue-700 text-white', 'muted' => 'text-blue-100', 'divider' => 'border-blue-500'],
        ['card' => 'bg-amber-500 text-slate-950', 'muted' => 'text-amber-950', 'divider' => 'border-amber-700/40'],
        ['card' => 'bg-indigo-700 text-white', 'muted' => 'text-indigo-100', 'divider' => 'border-indigo-500'],
    ];
@endphp

<article class="min-w-0 rounded-xl bg-indigo-100 p-3 lg:col-span-3">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 pb-2.5">
        <div class="flex items-center gap-2">
            <i data-lucide="users-round" class="h-4 w-4 text-indigo-600"></i>
            <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-slate-800">Ringkasan Per Regu</h2>
        </div>

        @if ($workshopDashboard['unknown_regu_count'] > 0)
            <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[9px] font-bold text-amber-700">
                Regu belum ditentukan: {{ $workshopDashboard['unknown_regu_count'] }}
            </span>
        @endif
    </div>

    <div class="mt-3 grid min-w-0 gap-3 sm:grid-cols-3">
        @foreach ($workshopDashboard['regu'] as $regu)
            @php($style = $reguStyles[$loop->index] ?? $reguStyles[0])
            <section class="min-w-0 rounded-lg p-3 {{ $style['card'] }}">
                <h3 class="break-words text-[10px] font-extrabold uppercase tracking-[0.08em]">{{ $regu['name'] }}</h3>
                <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] {{ $style['muted'] }}">Total</div>
                        <div class="mt-0.5 text-sm font-bold">{{ $regu['total'] }}</div>
                    </div>
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] {{ $style['muted'] }}">Proses</div>
                        <div class="mt-0.5 text-sm font-bold">{{ $regu['in_progress'] }}</div>
                    </div>
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] {{ $style['muted'] }}">Selesai</div>
                        <div class="mt-0.5 text-sm font-bold">{{ $regu['completed'] }}</div>
                    </div>
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] {{ $style['muted'] }}">Belum</div>
                        <div class="mt-0.5 text-sm font-bold">{{ $regu['incomplete'] }}</div>
                    </div>
                </div>
                <div class="mt-3 border-t pt-2 {{ $style['divider'] }}">
                    <span class="text-[9px] font-semibold {{ $style['muted'] }}">Penyelesaian</span>
                    <span class="float-right text-[10px] font-extrabold">{{ number_format($regu['completion_percentage'], 2, ',', '.') }}%</span>
                </div>
            </section>
        @endforeach
    </div>
</article>
