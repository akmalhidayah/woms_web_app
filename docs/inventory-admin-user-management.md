# Pengelolaan User Aplikasi Inventory

Menu **Inventory → User Aplikasi** mengelola akun Flutter yang tersimpan pada `inventory_users`. Akun ini terpisah dari akun admin WOMS pada tabel `users`.

## Route admin

- `GET /admin/inventory/users`
- `GET /admin/inventory/users/create`
- `POST /admin/inventory/users`
- `PATCH /admin/inventory/users/{inventoryUser}/status`
- `POST /admin/inventory/users/{inventoryUser}/reset-password`

Semua route memakai session admin WOMS, middleware role Admin, permission menu Inventory, dan CSRF.

## Data dan password

Akun menyimpan nama, email, nomor pegawai, telepon, jabatan, departemen, status aktif, status wajib ganti password, serta waktu login terakhir. Role ditentukan server sebagai `user`.

Saat akun dibuat atau password direset, sistem menghasilkan password sementara acak yang kuat. Password
disimpan melalui cast `hashed` dan plaintext hanya ditampilkan satu kali kepada admin setelah proses berhasil.
Password sementara tidak disimpan pada konfigurasi, URL, maupun log.

`must_change_password` ditetapkan `true` ketika akun dibuat atau password direset. Aplikasi Flutter tetap mengarahkan user ke flow perubahan password yang sudah tersedia.

## Aktivasi dan reset

Menonaktifkan akun mencabut semua token Sanctum sehingga seluruh sesi Flutter keluar. Mengaktifkan kembali akun tidak memulihkan token lama. Reset password juga mencabut semua token dan mewajibkan perubahan password.

Tidak ada registrasi mandiri, hard delete, perubahan history transaksi, ataupun penyimpanan password plaintext.

## Deployment

Backup database sebelum deployment, lalu smoke test create, login pertama, nonaktif, aktif, dan reset.
Tidak diperlukan konfigurasi password default pada environment.

Test utama berada di `tests/Feature/Inventory/Admin/InventoryAdminUserManagementTest.php` serta test autentikasi API Inventory.
