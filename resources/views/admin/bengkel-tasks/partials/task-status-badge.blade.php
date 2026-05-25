@php
    $status = $progressStatus ?? null;
    $label = $progressLabel ?? ($isCompleted ? 'Selesai' : 'Berjalan');
    $badgeClass = match ($status) {
        \App\Models\OrderWorkshop::PROGRESS_DONE => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL => 'bg-violet-100 text-violet-700 ring-violet-200',
        \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS => 'bg-blue-100 text-blue-700 ring-blue-200',
        \App\Models\OrderWorkshop::PROGRESS_PENDING => 'bg-orange-100 text-orange-700 ring-orange-200',
        \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL => 'bg-amber-100 text-amber-700 ring-amber-200',
        default => $isCompleted ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

@if ($isCompleted)
    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $badgeClass }}">
        <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
        {{ $label }}
    </span>
@else
    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $badgeClass }}">
        {{ $label }}
    </span>
@endif
