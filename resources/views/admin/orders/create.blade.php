<x-layouts.admin title="Tambah Order Pekerjaan">
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-blue-900 px-6 py-6 text-white shadow-sm">
            <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                <div class="space-y-3">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                        Order Admin
                    </span>
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight">Tambah Order Pekerjaan Jasa</h1>
                        <p class="max-w-2xl text-sm leading-7 text-blue-100">
                            Form ini dipakai untuk menyiapkan order baru sebelum dokumen pendukung diunggah dari halaman detail.
                        </p>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                    <div class="space-y-2">
                        <div class="text-sm font-semibold text-white">Checklist singkat</div>
                        <ul class="space-y-2 text-sm text-blue-100">
                            <li>Nomor order harus unik dan mudah dilacak.</li>
                            <li>Tentukan prioritas dan target pemakaian yang sesuai.</li>
                            <li>Setelah tersimpan, lanjutkan upload dokumen dari halaman detail.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.orders.store') }}" class="space-y-6">
            @include('admin.orders.partials.form', ['submitLabel' => 'Simpan Order'])
        </form>
    </div>
</x-layouts.admin>
