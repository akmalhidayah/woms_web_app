@if (($workPackages ?? collect())->isNotEmpty())
    <section class="rounded-2xl border border-blue-100 bg-blue-50/30 p-5">
        <h2 class="text-base font-bold text-slate-900">Pembagian Pekerjaan</h2>
        <div class="mt-3 space-y-2">
            @foreach ($workPackages as $package)
                @php
                    $isArray = is_array($package);
                    $displayNo = $isArray ? ($package['display_no'] ?? '-') : ($package->display_no ?? '-');
                    $jobName = $isArray ? ($package['job_name'] ?? '-') : ($package->job_name ?? '-');
                    $status = $isArray ? ($package['status'] ?? '-') : ($package->status ?? '-');
                    $assignments = $isArray ? ($package['assignments'] ?? []) : ($package->assignments ?? []);
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2"><span class="font-semibold text-blue-700">{{ $displayNo }} — {{ $jobName }}</span><span class="text-xs text-slate-500">{{ $status }}</span></div>
                    @if (count($assignments))
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">@foreach($assignments as $assignment)@php $assignmentArray = is_array($assignment); $picName = $assignmentArray ? ($assignment['pic_name'] ?? '-') : ($assignment->pic_name_snapshot ?? '-'); $descriptions = $assignmentArray ? ($assignment['work_descriptions'] ?? []) : ($assignment->work_descriptions ?? []); @endphp<div class="rounded border border-slate-100 bg-slate-50 px-2 py-1.5"><div class="font-semibold text-slate-700">{{ $picName }}</div><div class="text-xs text-slate-600">{{ implode('; ', (array) $descriptions) }}</div></div>@endforeach</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
