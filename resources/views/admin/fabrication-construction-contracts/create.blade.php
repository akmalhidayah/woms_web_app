<x-layouts.admin title="Buat Master Item Kontrak">
    @include('admin.fabrication-construction-contracts.partials.form', [
        'title' => 'Buat Master Item Kontrak',
        'subtitle' => 'Tambahkan satu item master harga kontrak untuk dipakai ulang di perhitungan HPP.',
        'submitRoute' => route('admin.fabrication-construction-contracts.store'),
        'isEdit' => false,
    ])
</x-layouts.admin>
