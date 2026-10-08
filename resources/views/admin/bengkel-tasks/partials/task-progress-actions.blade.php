@php
    $displayPackage = $task->getAttribute('display_work_package');
    $workshop = $task->order?->orderWorkshop;
    $workshopProgress = $workshop?->progress_status ?: $progressStatus;
    $waitingForStart = $workshop !== null
        && $workshopProgress === \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL
        && $workshop->started_at === null;
    $canAdvance = (bool) ($readiness['can_advance'] ?? false);
    $progressActions = [
        \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS => ['label' => 'Mulai Proses', 'icon' => 'play'],
        \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL => ['label' => 'Kirim ke QC', 'icon' => 'clipboard-check'],
        \App\Models\OrderWorkshop::PROGRESS_DONE => ['label' => 'Selesai', 'icon' => 'check-circle-2'],
    ];
@endphp

<div class="flex flex-col items-start gap-1.5">
    @if ($workshop?->started_at !== null)
        <div class="text-[9px] leading-4 text-slate-500">
            <div class="font-semibold uppercase tracking-[0.1em] text-slate-400">Mulai</div>
            <div class="font-semibold text-slate-700">{{ $workshop->started_at->format('d-m-Y H:i') }}</div>
        </div>
    @elseif ($workshopProgress === \App\Models\OrderWorkshop::PROGRESS_MENUNGGU_JADWAL)
        <span class="text-[10px] text-slate-500">Belum dimulai</span>
    @else
        <span class="text-[10px] leading-4 text-amber-700">Waktu mulai belum tercatat</span>
    @endif

    @if ($waitingForStart)
        <form action="{{ route('admin.bengkel-tasks.start', array_merge(['bengkel_task' => $task], $indexQuery)) }}" method="POST" class="start-bengkel-task-form">
            @csrf
            @method('PATCH')
            <button type="submit" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-2.5 text-[10px] font-semibold text-white transition hover:bg-blue-700">
                <i data-lucide="play" class="h-3.5 w-3.5"></i>
                Start Pekerjaan
            </button>
        </form>
    @elseif (! $displayPackage && ! $isCompleted)
        <details class="relative">
            <summary class="inline-flex h-8 cursor-pointer list-none items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100 [&::-webkit-details-marker]:hidden" title="Ubah progress pekerjaan">
                <i data-lucide="chevrons-up" class="h-3.5 w-3.5"></i>
                Ubah Progress
            </summary>
            <div class="absolute left-0 z-30 mt-1.5 w-44 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl">
                @foreach ($progressActions as $value => $action)
                    @continue($progressStatus === $value)
                    @continue($value === \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL && (trim((string) $task->catatan) === \App\Models\Order::WORKSHOP_REGU_ESTIMATOR || $task->order?->isEstimatorWorkshopRegu()))
                    @if ($canAdvance || $value === \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS)
                        <form action="{{ route('admin.bengkel-tasks.progress.update', array_merge(['bengkel_task' => $task], $indexQuery)) }}" method="POST" class="quick-progress-form" data-progress-label="{{ $action['label'] }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="progress_status" value="{{ $value }}">
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-semibold text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">
                                <i data-lucide="{{ $action['icon'] }}" class="h-3.5 w-3.5"></i>
                                {{ $action['label'] }}
                            </button>
                        </form>
                    @else
                        <button type="button" disabled class="flex w-full cursor-not-allowed items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-semibold text-slate-300" title="Selesaikan Persiapan Order terlebih dahulu">
                            <i data-lucide="{{ $action['icon'] }}" class="h-3.5 w-3.5"></i>
                            {{ $action['label'] }}
                        </button>
                    @endif
                @endforeach
                @if (! $canAdvance)
                    <p class="border-t border-slate-100 px-2.5 pt-2 text-[9px] leading-4 text-amber-700">Selesaikan Persiapan Order terlebih dahulu.</p>
                @endif
            </div>
        </details>
    @endif
</div>
