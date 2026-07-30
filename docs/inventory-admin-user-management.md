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

Password awal dibaca dari environment:

```dotenv
INVENTORY_DEFAULT_USER_PASSWORD=<password-sementara-yang-kuat>
```

Password tidak mempunyai fallback di source code dan disimpan melalui cast `hashed`. Jika konfigurasi kosong, pembuatan akun dan reset password ditolak. Password sementara hanya ditampilkan satu kali kepada admin setelah create/reset.

`must_change_password` ditetapkan `true` ketika akun dibuat atau password direset. Aplikasi Flutter tetap mengarahkan user ke flow perubahan password yang sudah tersedia.

## Aktivasi dan reset

Menonaktifkan akun mencabut semua token Sanctum sehingga seluruh sesi Flutter keluar. Mengaktifkan kembali akun tidak memulihkan token lama. Reset password juga mencabut semua token dan mewajibkan perubahan password.

Tidak ada registrasi mandiri, hard delete, perubahan history transaksi, ataupun penyimpanan password plaintext.

## Deployment

Backup database sebelum deployment. Isi environment variable production, jalankan `php artisan config:clear` atau strategi cache konfigurasi yang digunakan server, lalu smoke test create, login pertama, nonaktif, aktif, dan reset. Fitur ini tidak membutuhkan migration baru.

Test utama berada di `tests/Feature/Inventory/Admin/InventoryAdminUserManagementTest.php` serta test autentikasi API Inventory.
