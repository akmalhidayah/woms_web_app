<x-layouts.admin title="Edit QC Refurbish">
    @include('admin.orders.workshop.quality-control._refurbish-form', [
        'formTitle' => 'Edit Lembar Kerja Refurbish',
        'formDescription' => 'Perbarui data QC refurbish dan lampiran foto.',
        'action' => route('admin.orders.workshop.quality-control.update', [$order, $report]),
        'method' => 'PUT',
        'submitLabel' => 'Perbarui QC',
    ])
</x-layouts.admin>
