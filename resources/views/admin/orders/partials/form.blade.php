@csrf

@if ($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
        <div class="font-semibold">Periksa kembali data order pekerjaan.</div>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <i data-lucide="file-pen-line" class="h-5 w-5"></i>
            </span>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Informasi Utama</h2>
                <p class="text-sm text-slate-500">Data dasar order pekerjaan yang akan tampil di daftar admin.</p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="space-y-2">
                <label for="nomor_order" class="text-sm font-semibold text-slate-700">Nomor Order</label>
                <input
                    id="nomor_order"
                    name="nomor_order"
                    type="text"
                    value="{{ old('nomor_order', $order->nomor_order) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="ORD-2026-0001"
                    required
                >
            </div>

            <div class="space-y-2">
                <label for="notifikasi" class="text-sm font-semibold text-slate-700">Notifikasi</label>
                <input
                    id="notifikasi"
                    name="notifikasi"
                    type="text"
                    value="{{ old('notifikasi', $order->notifikasi) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="Nomor notifikasi"
                >
            </div>

            <div class="space-y-2">
                <label for="nama_pekerjaan" class="text-sm font-semibold text-slate-700">Nama Pekerjaan</label>
                <input
                    id="nama_pekerjaan"
                    name="nama_pekerjaan"
                    type="text"
                    value="{{ old('nama_pekerjaan', $order->nama_pekerjaan) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="Contoh: Perbaikan conveyor workshop"
                    required
                >
            </div>

            <div class="space-y-2">
                <label for="unit_kerja" class="text-sm font-semibold text-slate-700">Unit Kerja</label>
                <input
                    id="unit_kerja"
                    name="unit_kerja"
                    type="text"
                    value="{{ old('unit_kerja', $order->unit_kerja) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="Unit kerja pengusul"
                    required
                >
            </div>

            <div class="space-y-2">
                <label for="seksi" class="text-sm font-semibold text-slate-700">Seksi</label>
                <input
                    id="seksi"
                    name="seksi"
                    type="text"
                    value="{{ old('seksi', $order->seksi) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="Seksi terkait"
                    required
                >
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </span>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Prioritas dan Timeline</h2>
                <p class="text-sm text-slate-500">Tentukan urgensi pekerjaan dan target penyelesaiannya.</p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="space-y-2">
                <label for="prioritas" class="text-sm font-semibold text-slate-700">Prioritas</label>
                <select
                    id="prioritas"
                    name="prioritas"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    required
                >
                    @foreach ($priorityOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('prioritas', $order->prioritas) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label for="tanggal_order" class="text-sm font-semibold text-slate-700">Tanggal Order</label>
                <input
                    id="tanggal_order"
                    name="tanggal_order"
                    type="date"
                    value="{{ old('tanggal_order', optional($order->tanggal_order)->format('Y-m-d')) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    required
                >
            </div>

            <div class="space-y-2">
                <label for="target_selesai" class="text-sm font-semibold text-slate-700">Target Selesai</label>
                <input
                    id="target_selesai"
                    name="target_selesai"
                    type="date"
                    value="{{ old('target_selesai', optional($order->target_selesai)->format('Y-m-d')) }}"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    required
                >
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <i data-lucide="sticky-note" class="h-5 w-5"></i>
            </span>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Deskripsi dan Catatan</h2>
                <p class="text-sm text-slate-500">Berikan konteks pekerjaan agar mudah dipahami di halaman detail.</p>
            </div>
        </div>

        <div class="grid gap-5">
            <div class="space-y-2">
                <label for="deskripsi" class="text-sm font-semibold text-slate-700">Deskripsi</label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="5"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="Deskripsi lengkap order pekerjaan"
                    required
                >{{ old('deskripsi', $order->deskripsi) }}</textarea>
            </div>

            <div class="space-y-2">
                <label for="catatan" class="text-sm font-semibold text-slate-700">Catatan</label>
                <textarea
                    id="catatan"
                    name="catatan"
                    rows="4"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 focus:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    placeholder="Catatan tambahan admin"
                >{{ old('catatan', $order->catatan) }}</textarea>
            </div>
        </div>
    </section>

    <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">Nomor order harus unik. Setelah order dibuat, dokumen pendukung dapat diunggah dari halaman detail.</p>

        <div class="flex gap-3">
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                Kembali
            </a>
            <button type="submit" class="inline-flex items-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                {{ $submitLabel }}
            </button>
        </div>
    </div>
</div>
