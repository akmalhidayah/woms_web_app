<x-layouts.admin title="Edit Master Item Kontrak">
    @include('admin.fabrication-construction-contracts.partials.form', [
        'title' => 'Edit Master Item Kontrak',
        'subtitle' => 'Perbarui item master harga kontrak tanpa mengubah struktur item lain.',
        'submitRoute' => route('admin.fabrication-construction-contracts.update', $item),
        'isEdit' => true,
    ])
</x-layouts.admin>
