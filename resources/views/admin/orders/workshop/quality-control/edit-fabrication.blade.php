<x-layouts.admin title="Edit QC Fabrication">
    @include('admin.orders.workshop.quality-control._fabrication-form', [
        'formTitle' => 'Edit Quality Control Fabrication Record',
        'formDescription' => 'Perbarui data QC fabrikasi dan lampiran foto.',
        'action' => route('admin.orders.workshop.quality-control.update', [$order, $report]),
        'method' => 'PUT',
        'submitLabel' => 'Perbarui QC',
    ])
</x-layouts.admin>
