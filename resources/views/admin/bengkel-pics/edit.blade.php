<x-layouts.admin title="Edit PIC Bengkel">
    <form method="POST" action="{{ route('admin.bengkel-pics.update', $bengkel_pic) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.bengkel-pics._form', [
            'title' => 'Edit PIC Bengkel',
            'description' => 'Perbarui nama dan foto PIC bengkel.',
            'submitLabel' => 'Perbarui PIC',
        ])
    </form>
</x-layouts.admin>
