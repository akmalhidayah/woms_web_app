<x-layouts.admin title="Pembagian Pekerjaan Bengkel">
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

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ implode(' ', $errors->all()) }}</div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Paket Pekerjaan</h2>
                    <p class="text-xs text-slate-500">{{ $order->workPackageProgressLabel() }}</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Nomor paket dibuat otomatis</span>
            </div>

            <form method="POST" action="{{ route('admin.orders.workshop.work-packages.store', $order) }}" class="grid gap-3 rounded-xl border border-blue-100 bg-blue-50/40 p-4 md:grid-cols-2">
                @csrf
                <input name="job_name" required maxlength="255" placeholder="Nama pekerjaan paket" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <input name="target_date" type="date" value="{{ $order->target_selesai?->format('Y-m-d') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <textarea name="description" rows="2" placeholder="Deskripsi (opsional)" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm md:col-span-2"></textarea>
                <button class="w-fit rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Tambah Paket</button>
            </form>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-2">Paket</th><th class="px-3 py-2">Target</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">PIC</th><th class="px-3 py-2 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($workPackages as $package)
                            <tr>
                                <td class="px-3 py-3"><div class="font-semibold text-slate-900">{{ $package->displayNumber() }}</div><div class="text-xs text-slate-600">{{ $package->job_name }}</div></td>
                                <td class="px-3 py-3 text-slate-600">{{ $package->target_date?->format('d-m-Y') ?: '-' }}</td>
                                <td class="px-3 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $package->statusLabel() }}</span></td>
                                <td class="px-3 py-3 text-xs text-slate-600">{{ $package->assignments->pluck('pic_name_snapshot')->join(', ') ?: 'Belum ditentukan' }}</td>
                                <td class="px-3 py-3 text-right">
                                    @if (! $package->isLocked())
                                        <form method="POST" action="{{ route('admin.orders.workshop.work-packages.destroy', [$order, $package]) }}" onsubmit="return confirm('Hapus paket ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700">Hapus</button></form>
                                    @else
                                        <span class="text-xs text-slate-400">Terkunci</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">Belum ada paket pekerjaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
