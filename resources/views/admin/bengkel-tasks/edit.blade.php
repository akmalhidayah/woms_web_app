<x-layouts.admin title="Edit Pekerjaan Bengkel">
    <form method="POST" action="{{ route('admin.bengkel-tasks.update', array_merge(['bengkel_task' => $bengkel_task], request()->only(['q', 'regu', 'per_page', 'page']))) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.bengkel-tasks._form', [
            'task' => $bengkel_task,
            'title' => 'Edit Pekerjaan Bengkel',
            'description' => 'Lengkapi PIC, uraian pekerjaan, progress, dan lampiran untuk display bengkel.',
            'submitLabel' => 'Perbarui Pekerjaan',
        ])
    </form>
</x-layouts.admin>
