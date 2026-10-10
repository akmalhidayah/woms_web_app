@props(['inspection', 'routeName'])

@if ((new \App\Policies\EquipmentInspectionPolicy)->delete(auth()->user(), $inspection))
    <form method="POST" action="{{ route($routeName, $inspection) }}"
        data-swal-confirm
        data-swal-icon="warning"
        data-swal-title="Hapus laporan inspeksi?"
        data-swal-text="Permintaan approval aktif akan dibatalkan. Histori, tanda tangan, nomor dokumen, dan media audit tetap tersimpan."
        data-swal-confirm-text="Ya, hapus"
        data-swal-confirm-color="#dc2626">
        @csrf
        @method('DELETE')
        <input type="hidden" name="lock_version" value="{{ $inspection->lock_version }}">
        <button type="submit" title="Hapus inspeksi" aria-label="Hapus inspeksi {{ $inspection->form_name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100">
            <i data-lucide="trash-2" class="h-3 w-3" aria-hidden="true"></i>
        </button>
    </form>
@endif
