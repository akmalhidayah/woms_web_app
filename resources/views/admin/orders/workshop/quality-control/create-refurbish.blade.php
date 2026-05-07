<x-layouts.admin title="Tambah QC Refurbish">
    @include('admin.orders.workshop.quality-control._refurbish-form', [
        'formTitle' => 'Lembar Kerja Refurbish',
        'formDescription' => 'Isi data QC refurbish untuk order regu bengkel refurbish.',
        'action' => route('admin.orders.workshop.quality-control.store', $order),
        'method' => 'POST',
        'submitLabel' => 'Simpan QC',
    ])
</x-layouts.admin>
