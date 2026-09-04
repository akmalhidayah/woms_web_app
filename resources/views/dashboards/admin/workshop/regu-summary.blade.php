@php
    $reguStyles = [
        ['card' => 'bg-blue-700 text-white'],
        ['card' => 'bg-amber-500 text-slate-950'],
        ['card' => 'bg-indigo-700 text-white'],
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
                <div class="relative mt-2 h-[150px] min-w-0 w-full overflow-hidden rounded-lg bg-white/95 p-1">
                    <canvas
                        data-workshop-regu-chart="{{ $loop->index }}"
                        class="h-full w-full max-w-full"
                        role="img"
                        aria-label="Grafik {{ $regu['name'] }}"
                    ></canvas>
                </div>
            </section>
        @endforeach
    </div>
</article>
