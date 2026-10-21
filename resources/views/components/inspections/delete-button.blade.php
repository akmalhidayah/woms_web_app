@props(['inspection', 'routeName', 'size' => 'sm'])

@if ((new \App\Policies\EquipmentInspectionPolicy)->delete(auth()->user(), $inspection))
    @php
        $isAdminDeletion = auth()->user()?->isAdmin() ?? false;
        $buttonSizeClass = $size === 'md' ? 'h-8 w-8' : 'h-7 w-7';
        $iconSizeClass = $size === 'md' ? 'h-4 w-4' : 'h-3 w-3';
    @endphp
    <form method="POST" action="{{ route($routeName, $inspection) }}"
        data-swal-confirm
        data-swal-icon="warning"
        data-swal-title="Hapus laporan inspeksi?"
        data-swal-text="{{ $isAdminDeletion ? 'Permintaan approval aktif akan dibatalkan. Histori, tanda tangan, nomor dokumen, dan media audit tetap tersimpan.' : 'Draft asli ini akan dihapus dari daftar. Tindakan ini hanya tersedia sebelum laporan pernah disubmit.' }}"
        data-swal-confirm-text="Ya, hapus"
        data-swal-confirm-color="#dc2626">
        @csrf
        @method('DELETE')
        <input type="hidden" name="lock_version" value="{{ $inspection->lock_version }}">
        <button type="submit" title="Hapus inspeksi" aria-label="Hapus inspeksi {{ $inspection->form_name }}" class="inline-flex {{ $buttonSizeClass }} items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100">
            <i data-lucide="trash-2" class="{{ $iconSizeClass }}" aria-hidden="true"></i>
        </button>
    </form>
@endif
