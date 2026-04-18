<x-layouts.admin title="Edit Pekerjaan Bengkel">
    <form method="POST" action="{{ route('admin.bengkel-tasks.update', $bengkel_task) }}">
        @csrf
        @method('PUT')
        @include('admin.bengkel-tasks._form', [
            'task' => $bengkel_task,
            'title' => 'Edit Pekerjaan Bengkel',
            'description' => 'Perbarui informasi pekerjaan bengkel, regu, dan PIC yang ditampilkan.',
            'submitLabel' => 'Perbarui Pekerjaan',
        ])
    </form>
</x-layouts.admin>
