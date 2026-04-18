<x-layouts.admin title="Tambah Pekerjaan Bengkel">
    <form method="POST" action="{{ route('admin.bengkel-tasks.store') }}">
        @csrf
        @include('admin.bengkel-tasks._form', [
            'title' => 'Tambah Pekerjaan Bengkel',
            'description' => 'Buat item display pekerjaan bengkel beserta regu dan penanggung jawabnya.',
            'submitLabel' => 'Simpan Pekerjaan',
        ])
    </form>
</x-layouts.admin>
