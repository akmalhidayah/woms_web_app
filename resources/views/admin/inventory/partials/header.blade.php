<section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5 shadow-sm">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-600 ring-1 ring-blue-100">
            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ $title }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
        </div>
    </div>
</section>

@unless ($inventoryReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Tabel Inventory belum tersedia pada environment ini. Jalankan migration Inventory yang telah direview sebelum menggunakan modul.
    </div>
@endunless
