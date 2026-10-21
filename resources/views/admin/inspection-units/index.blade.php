<x-layouts.admin title="Master Unit Peralatan — WOMS">
    <div class="space-y-4">
        <section class="flex flex-col gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[.18em] text-blue-700">Inspeksi Peralatan</p><h1 class="mt-1 text-lg font-bold text-slate-900">Master Unit Peralatan</h1><p class="mt-1 text-sm text-slate-600">Satu unit fisik memiliki satu QR permanen dan histori inspeksi sendiri.</p></div>
            <a href="{{ route('admin.inspection-units.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white"><i data-lucide="plus" class="h-4 w-4"></i> Tambah Unit</a>
        </section>

        <x-inspections.sweet-alerts :success="session('success')" :error-messages="$errors->all()" />

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" class="grid gap-3 border-b border-slate-200 p-4 lg:grid-cols-[1.4fr_1fr_1fr_.8fr_auto]">
                <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode atau nama unit..." class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <select name="form_id" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Semua Master Form</option>@foreach ($forms as $form)<option value="{{ $form->id }}" @selected(($filters['form_id'] ?? null) == $form->id)>{{ $form->name }}</option>@endforeach</select>
                <select name="location" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Semua Lokasi</option>@foreach ($locations as $location)<option value="{{ $location }}" @selected(($filters['location'] ?? null) === $location)>{{ $location }}</option>@endforeach</select>
                <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Semua Status</option>@foreach (\App\Models\EquipmentInspectionUnit::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                <div class="flex gap-2"><button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Cari</button><a href="{{ route('admin.inspection-units.index') }}" title="Reset" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></a></div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-xs">
                    <thead class="bg-slate-100 text-[11px] uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Master Form</th><th class="px-4 py-3">Lokasi</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Histori Approved</th><th class="px-4 py-3 text-center">QR Publik</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($units as $unit)
                            <tr class="align-top">
                                <td class="px-4 py-3"><p class="font-bold text-slate-900">{{ $unit->asset_code }}</p><p class="mt-1 text-slate-600">{{ $unit->form?->name ?: $unit->name }}</p></td>
                                <td class="px-4 py-3">{{ $unit->form->name }}</td>
                                <td class="px-4 py-3">{{ $unit->location ?: '—' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 font-semibold {{ $unit->lifecycle_status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($unit->lifecycle_status === 'retired' ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst($unit->lifecycle_status) }}</span></td>
                                <td class="px-4 py-3"><p class="font-bold">{{ $unit->approved_history_count }}</p><p class="mt-1 text-[11px] text-slate-500">Terakhir: {{ $unit->last_approved_at ? \Illuminate\Support\Carbon::parse($unit->last_approved_at)->format('d/m/Y') : 'Belum ada' }}</p></td>
                                <td class="px-4 py-3 text-center"><span class="rounded-full px-2 py-1 font-semibold {{ $unit->public_enabled ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600' }}">{{ $unit->public_enabled ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="px-4 py-3"><div class="flex justify-center gap-2"><a href="{{ route('admin.inspection-units.show', $unit) }}" title="Detail & QR" aria-label="Detail {{ $unit->asset_code }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700"><i data-lucide="qr-code" class="h-4 w-4"></i></a><a href="{{ route('admin.inspection-units.edit', $unit) }}" title="Edit" aria-label="Edit {{ $unit->asset_code }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700"><i data-lucide="pencil" class="h-4 w-4"></i></a></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada Master Unit Peralatan. Tambahkan unit fisik sebelum mengaktifkan kewajiban pemilihan unit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $units->links() }}</div>
        </section>
    </div>
</x-layouts.admin>
