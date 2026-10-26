# Optimasi pembacaan layout Admin

Layout, definisi menu, rule eligibility badge, workflow approval, dan dokumen
tetap menggunakan implementasi WOMS existing. Optimasi ini membatasi pekerjaan
query yang berulang saat membuka halaman Admin.

## Perubahan

- `AdminPresentationData` dipakai bersama sidebar, header, banner dashboard,
  panel notifikasi, dan action feed. Satu instance beserta `AdminActionCenter`
  diikat ke request aktif, bukan singleton lintas pengguna/worker.
- Ringkasan badge/jumlah aksi, daftar aksi, dan daftar informasi memakai cache
  Laravel existing selama 45 detik. Key mencakup ID pengguna, role, subrole,
  daftar izin menu terurut, dan origin. Read state/inspeksi pribadi tidak
  dibagikan kepada pengguna lain yang memiliki role sama.
- Cache hanya untuk presentasi. Controller pemrosesan dokumen dan middleware
  otorisasi tidak menggunakan angka/daftar cache sebagai dasar keputusan.
  Jika cache store sedang gagal, data presentasi tetap dihitung langsung dan
  kegagalan cache dilaporkan tanpa menggagalkan respons bisnis yang sudah sukses.
- Izin menu dibaca sekali per role dalam request, tanpa cache lintas request.
  Perubahan melalui model maupun bulk update Access Control menghapus memo
  request. Request berikutnya selalu membaca izin terbaru.
- `RefreshAdminPresentationCache`, pada middleware web, menghapus cache milik
  admin aktif setelah respons write berhasil (termasuk redirect). Tidak ada
  perubahan status dokumen. Perubahan oleh pengguna lain/background job dapat
  tertunda tampil maksimal sekitar 45 detik pada request berikutnya.
- Daftar aksi dan informasi dimuat melalui `GET /admin/notifications/panel`
  saat lonceng dibuka. HTML memakai partial dari markup lama, dengan CSRF baru
  setiap respons. HTML/CSRF tidak disimpan di cache bersama. Endpoint dijaga
  `auth` dan `role:admin`, lalu isi tetap difilter izin menu.
- Penghitung informasi hanya mengambil identitas record (ID dan waktu update
  untuk progress), bukan seluruh signature atau relasi dokumen. Predicate
  status dan batas 7 hari dipakai bersama dengan daftar informasi. Query sumber
  yang menunya tidak diizinkan dilewati. Pemeriksaan tabel read tidak lagi
  diulang untuk setiap notifikasi.
- Polling browser, jika diaktifkan pengguna, menjadi 180 detik. Permintaan
  bersamaan dan event visibility yang berdekatan memakai respons yang sama
  selama 45 detik. Pengaturan opt-in dan aturan notifikasi existing tetap.
- Struktur Organisasi memuat pilihan pengguna saat modal pertama dibuka lewat
  `GET /admin/struktur-organisasi/user-options`, dengan middleware izin struktur
  yang sama. Respons hanya `id` dan `name`. Pilihan disimpan untuk halaman aktif;
  validasi backend tetap memeriksa user/relasi saat submit. Form diblokir sampai
  pilihan berhasil dimuat, termasuk saat memulihkan old input.
- Query relasi/kolom yang tidak dipakai pada Struktur Organisasi dihapus.
  Membuka index tidak lagi membuat record konfigurasi HPP kosong; penyimpanan
  konfigurasi tetap dilakukan oleh action update existing.

Hierarki organisasi, vendor, dan konfigurasi HPP yang diperlukan oleh halaman
masih dibaca server. Zoom/level awal bagan tetap. CSS/JS tidak dipindahkan ke
Vite dan logo tidak diubah dalam patch ini.

## Verifikasi

Source regression test mencakup lazy loading, cache per user dan permission,
TTL, mark-as-read, pencabutan akses, query izin sekali per request, akses kedua
endpoint baru, minimisasi data pengguna, dan index struktur tanpa write.

Lingkungan pengerjaan tidak menyediakan PHP, Node, atau `vendor`; syntax check
PHP, PHPUnit, Pint, route:list, kompilasi Blade, dan benchmark database belum
dijalankan. Pemeriksaan statis/diff dan simulasi JavaScript terisolasi tidak
menggantikan uji Laravel atau browser.

Pada environment testing dengan dependency tersedia, gunakan database test
terisolasi (konfigurasi PHPUnit repository menggunakan SQLite in-memory):

```bash
php artisan test --filter='AdminPresentationPerformanceTest|AdminNotificationAccessTest|AdminBrowserNotificationFeedTest|AdminActionBannerTest|AdminSidebarBadgeCounterTest|AccessControlTest|StructureOrganizationVendorTest|StructureOrganizationSectionStabilityTest'
php artisan route:list --path=admin/notifications
php artisan route:list --path=admin/struktur-organisasi
```

Periksa manual: buka lonceng, gagal jaringan/coba lagi, baca satu/baca semua,
banner aksi, menu Admin biasa/Karyawan/Super Admin, modal create/edit struktur,
modal vendor/HPP, old input setelah validasi gagal, serta impersonation.
Bandingkan query count dan latency pada cache dingin, cache hangat, setelah
save, dan setelah TTL habis. Belum ada klaim angka percepatan runtime.

## Deployment

Tidak ada migration, seeder, dependency baru, atau perubahan entry point aset.
Cache store existing harus berfungsi; store `array` tidak berbagi cache antar
request server sehingga manfaat cache 45 detik tidak berlaku di production
dengan driver tersebut. Gunakan strategi deployment/cache view dan route yang
sudah berlaku di server. Tidak ada build, clear cache, push, atau deployment
yang dijalankan otomatis dalam pekerjaan ini.

Badge pada halaman yang terus terbuka tidak disegarkan otomatis oleh patch ini.
TTL adalah batas umur cache server, bukan jaminan layar tanpa refresh berubah
setiap 45 detik. Membuka ulang lonceng setelah 45 detik meminta panel terbaru.
