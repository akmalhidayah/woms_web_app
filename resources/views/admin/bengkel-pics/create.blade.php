<x-layouts.admin title="Tambah PIC Bengkel">
    <form method="POST" action="{{ route('admin.bengkel-pics.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.bengkel-pics._form', [
            'title' => 'Tambah PIC Bengkel',
            'description' => 'Tambahkan daftar penanggung jawab yang bisa dipilih di pekerjaan bengkel.',
            'submitLabel' => 'Simpan PIC',
        ])
    </form>
</x-layouts.admin>
