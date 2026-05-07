<x-layouts.admin title="Tambah QC Fabrication">
    @include('admin.orders.workshop.quality-control._fabrication-form', [
        'formTitle' => 'Quality Control Fabrication Record',
        'formDescription' => 'Isi data QC untuk order regu fabrikasi.',
        'action' => route('admin.orders.workshop.quality-control.store', $order),
        'method' => 'POST',
        'submitLabel' => 'Simpan QC',
    ])
</x-layouts.admin>
