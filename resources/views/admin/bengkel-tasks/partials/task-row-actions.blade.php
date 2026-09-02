@php
    $attachmentPayload = $attachmentPayload ?? null;
    $isMobile = (bool) ($mobile ?? false);
    $badge = $badge ?? null;
    $readiness = is_array($readiness ?? null) ? $readiness : null;
    $displayPackage = $task->getAttribute('display_work_package');
    $canAdvance = (bool) ($readiness['can_advance'] ?? false);
    $progressActions = [
        \App\Models\OrderWorkshop::PROGRESS_IN_PROGRESS => ['label' => 'Mulai Proses', 'icon' => 'play'],
        \App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL => ['label' => 'Kirim ke QC', 'icon' => 'clipboard-check'],
        \App\Models\OrderWorkshop::PROGRESS_DONE => ['label' => 'Selesai', 'icon' => 'check-circle-2'],
    ];
@endphp

<div class="flex flex-col gap-2 {{ $isMobile ? 'w-full items-start' : 'items-end' }}">
    @if ($badge)
        <div class="flex max-w-[170px] flex-wrap items-center gap-1.5 {{ $isMobile ? 'justify-start' : 'justify-end' }}">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $badge['class'] }}">
                {{ $badge['label'] }}
            </span>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-1.5 {{ $isMobile ? 'justify-start' : 'justify-end' }}">
        @if (! $displayPackage && ! $isCompleted)
            <details class="relative">
                <summary class="inline-flex h-8 cursor-pointer list-none items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100 [&::-webkit-details-marker]:hidden" title="Ubah progress pekerjaan">
                    <i data-lucide="chevrons-up" class="h-3.5 w-3.5"></i>
                    Ubah Progress
                </summary>
                <div class="absolute {{ $isMobile ? 'left-0' : 'right-0' }} z-30 mt-1.5 w-44 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl">
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

        @if ($attachmentPayload)
            <button type="button" @click="openAttachment(@js($attachmentPayload))" title="Preview Lampiran" aria-label="Preview lampiran" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-blue-600 transition hover:bg-slate-50">
                <i data-lucide="{{ ($attachmentPayload['is_image'] ?? false) ? 'image' : 'file-text' }}" class="h-3.5 w-3.5"></i>
            </button>
        @endif

        <a href="{{ route('admin.bengkel-tasks.edit', array_merge(['bengkel_task' => $task], $indexQuery)) }}" title="Edit" aria-label="Edit pekerjaan" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50">
            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
        </a>

        <form action="{{ route('admin.bengkel-tasks.archive', array_merge(['bengkel_task' => $task], $indexQuery)) }}" method="POST" class="archive-bengkel-task-form">
            @csrf
            @method('PATCH')
            <button type="submit" title="Arsipkan" aria-label="Arsipkan pekerjaan" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-blue-600 transition hover:bg-slate-50">
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
            </button>
        </form>
    </div>
</div>
