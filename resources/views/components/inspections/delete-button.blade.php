@props(['inspection', 'routeName'])

@if ((new \App\Policies\EquipmentInspectionPolicy)->delete(auth()->user(), $inspection))
    <form method="POST" action="{{ route($routeName, $inspection) }}" onsubmit="return confirm('Hapus laporan inspeksi ini dari daftar? Permintaan approval aktif akan dibatalkan. Histori dan tanda tangan tetap tersimpan.')">
        @csrf
        @method('DELETE')
        <input type="hidden" name="lock_version" value="{{ $inspection->lock_version }}">
        <button type="submit" title="Hapus inspeksi" aria-label="Hapus inspeksi {{ $inspection->form_name }}" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 p-2 text-red-600 hover:bg-red-100">
            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
        </button>
    </form>
@endif
