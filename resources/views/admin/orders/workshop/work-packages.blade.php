<x-layouts.admin title="Pembagian Pekerjaan Bengkel">
    @php
        $orderLocked = $order->qualityControlReports->isNotEmpty()
            || $order->workshopHandover !== null
            || in_array($order->orderWorkshop?->progress_status, [\App\Models\OrderWorkshop::PROGRESS_QUALITY_CONTROL, \App\Models\OrderWorkshop::PROGRESS_DONE], true)
            || $order->bengkelTasks->contains(fn ($task) => $task->archived_at !== null);
    @endphp
    <div class="space-y-4">
        <section class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pembagian Pekerjaan</p>
                    <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $order->nomor_order }} — {{ $order->nama_pekerjaan }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $order->unit_kerja }} · {{ $order->seksi }}</p>
                </div>
                <a href="{{ route('admin.orders.workshop.index', ['search' => $order->nomor_order]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Kembali</a>
            </div>
        </section>

        @if (session('status')) <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ implode(' ', $errors->all()) }}</div> @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-semibold text-slate-900">Paket Pekerjaan</h2><p class="text-xs text-slate-500">{{ $order->workPackageProgressLabel() }}</p></div>
                @if (! $orderLocked)<span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Nomor paket dibuat otomatis</span>@else<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Terkunci karena Quality Control/Serah Terima dimulai</span>@endif
            </div>

            @if (! $orderLocked && $workPackages->isEmpty())
                <form method="POST" action="{{ route('admin.orders.workshop.work-packages.batch', $order) }}" class="space-y-3 rounded-xl border border-blue-100 bg-blue-50/40 p-4" id="package-batch-form">
                    @csrf
                    <div id="package-batch-rows" class="space-y-3">
                        @for ($i = 0; $i < 2; $i++)
                            <div class="package-batch-row rounded-lg border border-blue-100 bg-white p-3" data-package-index="{{ $i }}">
                                <div class="grid gap-2 md:grid-cols-12">
                                    <input name="packages[{{ $i }}][job_name]" required maxlength="255" value="{{ old("packages.$i.job_name") }}" placeholder="Nama pekerjaan paket" class="md:col-span-4 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <input name="packages[{{ $i }}][target_date]" type="date" value="{{ old("packages.$i.target_date", $order->target_selesai?->format('Y-m-d')) }}" class="md:col-span-3 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <input name="packages[{{ $i }}][description]" maxlength="5000" value="{{ old("packages.$i.description") }}" placeholder="Deskripsi (opsional)" class="md:col-span-4 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button type="button" class="remove-package-row hidden rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 md:col-span-1">Hapus</button>
                                </div>
                                <div class="assignment-rows mt-3 space-y-2" data-package-index="{{ $i }}">
                                    <div class="assignment-row grid gap-2 rounded border border-slate-200 bg-slate-50 p-2 md:grid-cols-12" data-assignment-index="0">
                                        <select name="packages[{{ $i }}][assignments][0][pic_id]" class="md:col-span-4 rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">PIC (opsional)</option>@foreach ($bengkelPics as $pic)<option value="{{ $pic->id }}">{{ $pic->name }}</option>@endforeach</select>
                                        <div class="description-rows space-y-2 md:col-span-7"><input name="packages[{{ $i }}][assignments][0][descriptions][0]" maxlength="1000" placeholder="Uraian PIC (opsional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                                        <div class="flex gap-1 md:col-span-1"><button type="button" class="add-description rounded border border-blue-200 px-2 text-xs text-blue-700">+ Uraian</button><button type="button" class="remove-assignment hidden rounded border border-rose-200 px-2 text-xs text-rose-700">×</button></div>
                                    </div>
                                </div>
                                <button type="button" class="add-assignment mt-2 rounded border border-blue-200 px-2 py-1 text-xs font-semibold text-blue-700">+ PIC</button>
                            </div>
                        @endfor
                    </div>
                    <div class="flex flex-wrap gap-2"><button type="button" id="add-package-row" class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700">Tambah Package</button><button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Simpan Pembagian</button></div>
                    <p class="text-xs text-slate-500">Minimal 2 dan maksimal 99 package. PIC dapat dilengkapi setelah package dibuat.</p>
                </form>
            @elseif ($orderLocked && $workPackages->isEmpty())
                <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Belum ada paket pekerjaan. Pembagian tidak dapat ditambahkan karena proses lanjutan telah dimulai.</p>
            @endif

            @if (! $orderLocked && $workPackages->isNotEmpty())
                <form method="POST" action="{{ route('admin.orders.workshop.work-packages.store', $order) }}" class="mb-5 grid gap-3 rounded-xl border border-blue-100 bg-blue-50/40 p-4 md:grid-cols-2">
                    @csrf
                    <input name="job_name" required maxlength="255" placeholder="Nama pekerjaan paket" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><input name="target_date" type="date" value="{{ $order->target_selesai?->format('Y-m-d') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><textarea name="description" rows="2" placeholder="Deskripsi (opsional)" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2"></textarea><button class="w-fit rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Tambah Paket</button>
                </form>
            @endif

            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-2">Paket</th><th class="px-3 py-2">Target</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">PIC &amp; Uraian</th><th class="px-3 py-2 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($workPackages as $package)
                    <tr>
                        <td class="px-3 py-3 align-top"><div class="font-semibold text-slate-900">{{ $package->displayNumber() }}</div><div class="text-xs text-slate-600">{{ $package->job_name }}</div><div class="mt-1 text-xs text-slate-500">{{ $package->description ?: 'Tanpa deskripsi' }}</div></td>
                        <td class="px-3 py-3 align-top text-slate-600">{{ $package->target_date?->format('d-m-Y') ?: '-' }}<br>@if($package->completed_at)<span class="text-xs text-emerald-600">Selesai {{ $package->completed_at->format('d-m-Y H:i') }}</span>@endif</td>
                        <td class="px-3 py-3 align-top"><form method="POST" action="{{ route('admin.orders.workshop.work-packages.status.update', [$order, $package]) }}" class="space-y-1">@csrf @method('PATCH')<select name="status" class="rounded-lg border border-slate-300 px-2 py-1 text-xs" @disabled($package->isLocked())>@foreach(\App\Models\WorkshopWorkPackage::statusOptions() as $value => $label)<option value="{{ $value }}" @selected($package->status === $value)>{{ $label }}</option>@endforeach</select><input name="pending_reason" value="{{ $package->pending_reason }}" placeholder="Alasan pending" class="w-full rounded-lg border border-slate-300 px-2 py-1 text-xs" @disabled($package->isLocked())>@if(! $package->isLocked())<button class="rounded bg-blue-600 px-2 py-1 text-[11px] font-semibold text-white">Simpan status</button>@endif</form></td>
                        <td class="px-3 py-3 align-top text-xs text-slate-600"><div class="space-y-2">@forelse($package->assignments as $assignment)<div class="rounded border border-slate-200 bg-slate-50 p-2"><div class="font-semibold text-slate-800">{{ $assignment->pic_name_snapshot }}</div><ul class="list-disc pl-4">@foreach($assignment->work_descriptions ?? [] as $description)<li>{{ $description }}</li>@endforeach</ul></div>@empty<span class="text-slate-400">Belum ditentukan</span>@endforelse</div></td>
                        <td class="px-3 py-3 text-right align-top"><div class="flex flex-col items-end gap-2">@if(! $package->isLocked())<details class="text-left"><summary class="cursor-pointer rounded-lg border border-blue-200 px-2 py-1 text-xs font-semibold text-blue-700">Edit</summary><form method="POST" action="{{ route('admin.orders.workshop.work-packages.update', [$order, $package]) }}" class="package-edit-form mt-2 w-72 space-y-2 rounded-lg border border-slate-200 bg-white p-2">@csrf @method('PATCH')<input name="job_name" required value="{{ $package->job_name }}" class="w-full rounded border border-slate-300 px-2 py-1 text-xs"><input name="target_date" type="date" required value="{{ $package->target_date?->format('Y-m-d') }}" class="w-full rounded border border-slate-300 px-2 py-1 text-xs"><textarea name="description" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">{{ $package->description }}</textarea><div class="edit-assignment-rows space-y-2">@forelse($package->assignments as $aIndex => $assignment)<div class="edit-assignment-row rounded border border-slate-200 p-2" data-assignment-index="{{ $aIndex }}"><div class="flex gap-1"><select name="assignments[{{ $aIndex }}][pic_id]" class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1 text-xs"><option value="">Pilih PIC</option>@foreach($bengkelPics as $pic)<option value="{{ $pic->id }}" @selected((int)$assignment->bengkel_pic_id === (int)$pic->id)>{{ $pic->name }}</option>@endforeach</select><button type="button" class="remove-edit-assignment rounded border border-rose-200 px-2 text-xs text-rose-700">×</button></div><div class="edit-description-rows mt-1 space-y-1">@forelse($assignment->work_descriptions ?? [] as $dIndex => $description)<div class="flex gap-1"><input name="assignments[{{ $aIndex }}][descriptions][{{ $dIndex }}]" value="{{ $description }}" class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1 text-xs"><button type="button" class="remove-edit-description rounded border border-rose-200 px-1 text-xs text-rose-700">×</button></div>@empty<div class="flex gap-1"><input name="assignments[{{ $aIndex }}][descriptions][0]" class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Uraian pekerjaan"><button type="button" class="remove-edit-description rounded border border-rose-200 px-1 text-xs text-rose-700">×</button></div>@endforelse</div><button type="button" class="add-edit-description mt-1 rounded border border-blue-200 px-2 py-1 text-[11px] text-blue-700">+ Uraian</button></div>@empty<div class="edit-assignment-row rounded border border-slate-200 p-2" data-assignment-index="0"><div class="flex gap-1"><select name="assignments[0][pic_id]" class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1 text-xs"><option value="">Pilih PIC</option>@foreach($bengkelPics as $pic)<option value="{{ $pic->id }}">{{ $pic->name }}</option>@endforeach</select><button type="button" class="remove-edit-assignment hidden rounded border border-rose-200 px-2 text-xs text-rose-700">×</button></div><div class="edit-description-rows mt-1"><input name="assignments[0][descriptions][0]" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Uraian pekerjaan"></div><button type="button" class="add-edit-description mt-1 rounded border border-blue-200 px-2 py-1 text-[11px] text-blue-700">+ Uraian</button></div>@endforelse</div><button type="button" class="add-edit-assignment rounded border border-blue-200 px-2 py-1 text-xs text-blue-700">+ PIC</button><button class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">Simpan perubahan</button></form></details><form method="POST" action="{{ route('admin.orders.workshop.work-packages.destroy', [$order, $package]) }}" onsubmit="return confirm('Hapus paket ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700">Hapus</button></form>@else<span class="text-xs text-slate-400">Terkunci</span>@endif</div></td>
                    </tr>
                @empty <tr><td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">Belum ada paket pekerjaan.</td></tr>@endforelse
            </tbody></table></div>
            @if ($workPackages->isNotEmpty() && $workPackages->every(fn ($package) => $package->isCompleted()) && ! $orderLocked && auth()->user() && \App\Support\AdminMenuRegistry::canAccess(auth()->user(), \App\Support\AdminMenuRegistry::MENU_ORDER_BENGKEL))
                <a href="{{ route('admin.orders.workshop.index', ['search' => $order->nomor_order]) }}" class="mt-4 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Kembali ke Order Bengkel untuk Set QC</a>
            @endif
        </section>
    </div>
    @if (! $orderLocked)
        <script>
            (() => {
                const pics = @json($bengkelPics->map(fn ($pic) => ['id' => $pic->id, 'name' => $pic->name])->values());
                const optionHtml = (empty = 'PIC (opsional)') => `<option value="">${empty}</option>${pics.map((pic) => `<option value="${pic.id}">${String(pic.name).replaceAll('"', '&quot;')}</option>`).join('')}`;
                const descriptionInput = (name) => `<input name="${name}" maxlength="1000" placeholder="Uraian pekerjaan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">`;

                const renumberAssignments = (container, rowSelector, descriptionSelector, packageIndex = null) => {
                    const rows = [...container.querySelectorAll(rowSelector)];
                    const packagePrefix = packageIndex === null ? '' : `packages[${packageIndex}]`;

                    rows.forEach((row, assignmentIndex) => {
                        row.dataset.assignmentIndex = assignmentIndex;
                        const assignmentPrefix = packagePrefix
                            ? `${packagePrefix}[assignments][${assignmentIndex}]`
                            : `assignments[${assignmentIndex}]`;
                        row.querySelector('select').name = `${assignmentPrefix}[pic_id]`;
                        row.querySelectorAll(`${descriptionSelector} input`).forEach((element, descriptionIndex) => {
                            element.name = `${assignmentPrefix}[descriptions][${descriptionIndex}]`;
                        });
                        row.querySelector('.remove-assignment, .remove-edit-assignment')?.classList.toggle('hidden', rows.length < 2);
                    });
                };

                const renumberBatch = (wrap) => {
                    [...wrap.querySelectorAll('.package-batch-row')].forEach((row, packageIndex) => {
                        row.dataset.packageIndex = packageIndex;
                        row.querySelector('input[name$="[job_name]"]').name = `packages[${packageIndex}][job_name]`;
                        row.querySelector('input[name$="[target_date]"]').name = `packages[${packageIndex}][target_date]`;
                        row.querySelector('textarea[name$="[description]"]').name = `packages[${packageIndex}][description]`;
                        renumberAssignments(row, '.assignment-row', '.description-rows', packageIndex);
                        row.querySelector('.remove-package-row')?.classList.toggle('hidden', wrap.querySelectorAll('.package-batch-row').length < 3);
                    });
                };

                const renumberEdit = (form) => {
                    renumberAssignments(form.querySelector('.edit-assignment-rows'), '.edit-assignment-row', '.edit-description-rows');
                };

                const batch = document.getElementById('package-batch-form');
                if (batch) {
                    const wrap = document.getElementById('package-batch-rows');
                    renumberBatch(wrap);

                    document.getElementById('add-package-row')?.addEventListener('click', () => {
                        const rows = wrap.querySelectorAll('.package-batch-row');
                        if (rows.length >= 99) return;

                        const clone = rows[0].cloneNode(true);
                        clone.querySelectorAll('input, select, textarea').forEach((element) => {
                            if (element.type !== 'date') element.value = '';
                        });
                        clone.querySelector('.remove-package-row')?.classList.remove('hidden');
                        wrap.appendChild(clone);
                        renumberBatch(wrap);
                    });

                    wrap.addEventListener('click', (event) => {
                        const target = event.target;
                        const row = target.closest('.package-batch-row');
                        if (!row) return;

                        if (target.closest('.remove-package-row')) {
                            row.remove();
                            renumberBatch(wrap);
                            return;
                        }

                        if (target.closest('.add-assignment')) {
                            const list = row.querySelector('.assignment-rows');
                            const item = document.createElement('div');
                            item.className = 'assignment-row grid gap-2 rounded border border-slate-200 bg-slate-50 p-2 md:grid-cols-12';
                            item.innerHTML = `<select class="md:col-span-4 rounded-lg border border-slate-300 px-3 py-2 text-sm">${optionHtml()}</select><div class="description-rows space-y-2 md:col-span-7">${descriptionInput('')}</div><div class="flex gap-1 md:col-span-1"><button type="button" class="add-description rounded border border-blue-200 px-2 text-xs text-blue-700">+ Uraian</button><button type="button" class="remove-assignment rounded border border-rose-200 px-2 text-xs text-rose-700">×</button></div>`;
                            list.appendChild(item);
                            renumberBatch(wrap);
                            return;
                        }

                        if (target.closest('.remove-assignment')) {
                            target.closest('.assignment-row')?.remove();
                            renumberBatch(wrap);
                            return;
                        }

                        if (target.closest('.add-description')) {
                            target.closest('.assignment-row')?.querySelector('.description-rows')?.insertAdjacentHTML('beforeend', descriptionInput(''));
                            renumberBatch(wrap);
                        }
                    });
                }

                document.querySelectorAll('.package-edit-form').forEach((form) => {
                    renumberEdit(form);
                    form.addEventListener('click', (event) => {
                        const target = event.target;
                        const list = form.querySelector('.edit-assignment-rows');

                        if (target.closest('.remove-edit-assignment')) {
                            target.closest('.edit-assignment-row')?.remove();
                            renumberEdit(form);
                            return;
                        }

                        if (target.closest('.remove-edit-description')) {
                            target.closest('.remove-edit-description').parentElement?.remove();
                            renumberEdit(form);
                            return;
                        }

                        if (target.closest('.add-edit-assignment')) {
                            const item = document.createElement('div');
                            item.className = 'edit-assignment-row rounded border border-slate-200 p-2';
                            item.innerHTML = `<div class="flex gap-1"><select class="min-w-0 flex-1 rounded border border-slate-300 px-2 py-1 text-xs">${optionHtml('Pilih PIC')}</select><button type="button" class="remove-edit-assignment rounded border border-rose-200 px-2 text-xs text-rose-700">×</button></div><div class="edit-description-rows mt-1">${descriptionInput('')}</div><button type="button" class="add-edit-description mt-1 rounded border border-blue-200 px-2 py-1 text-[11px] text-blue-700">+ Uraian</button>`;
                            list.appendChild(item);
                            renumberEdit(form);
                            return;
                        }

                        if (target.closest('.add-edit-description')) {
                            target.closest('.edit-assignment-row')?.querySelector('.edit-description-rows')?.insertAdjacentHTML('beforeend', descriptionInput(''));
                            renumberEdit(form);
                        }
                    });
                });
            })();
        </script>
    @endif
</x-layouts.admin>
