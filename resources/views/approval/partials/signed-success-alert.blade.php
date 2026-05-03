@if (session('approval_signed'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Swal) {
                return;
            }

            window.Swal.fire({
                icon: 'success',
                title: 'Tanda tangan tersimpan',
                text: @json(session('status', 'Tanda tangan berhasil disimpan.')),
                showCancelButton: true,
                confirmButtonText: 'Ke Dashboard',
                cancelButtonText: 'Tetap di Sini',
                reverseButtons: true,
                confirmButtonColor: '#0f172a',
                cancelButtonColor: '#64748b',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = @json(route(auth()->user()->dashboardRouteName()));
                }
            });
        });
    </script>
@endif
