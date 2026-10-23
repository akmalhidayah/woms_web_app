# QR Peralatan: Inspeksi, Riwayat, dan Standar CILT

## Perubahan

Satu URL `/equipment/q/{publicCode}` sekarang menyediakan tiga Tab Card Alpine:

- **Inspeksi** (default): hasil approved terbaru yang lolos validasi signed snapshot, nama Inspektor dari snapshot, nomor/tanggal dokumen, A/B/C, kategori/item, remarks asli, dan PDF inspeksi existing.
- **Riwayat**: laporan approved unit yang sama, terbaru dahulu, dengan detail checklist/remarks yang dapat dibuka. Tombol Sebelumnya/Berikutnya menggunakan pagination server-side. Tab tetap Riwayat saat berpindah halaman; tab Inspeksi tetap berisi pemeriksaan valid terbaru, termasuk ketika berada di halaman kedua.
- **CILT**: satu PDF standar referensi per unit, nama dokumen dan tanggal update. PDF dibuka melalui tab browser baru. Dokumen tidak diunduh saat pergantian tab.

Pemilihan tab tidak membuat request atau mengganti URL. Navigasi pagination saja menggunakan query `page` dan `tab=history`. Tombol tab mendukung Enter/Space, panah kanan/kiri, Home/End, `role=tab`, `aria-selected`, dan relasi panel. Disclaimer keselamatan selalu berada di luar panel. CSS halaman mengikuti pola standalone existing; Alpine berasal dari runtime Livewire existing, tanpa dependency atau build entry baru.

Halaman mempunyai empty state terpisah untuk belum ada pemeriksaan approved, belum ada histori approved, dan belum tersedia PDF CILT. Path CILT yang ada tetapi file fisiknya hilang juga menghasilkan empty state, sementara endpoint dokumen mengembalikan 404 yang aman dan mencatat unit ID pada log internal.

## Perbaikan histori

Implementasi lama melakukan pagination sebelum validasi integritas sehingga jumlah halaman memasukkan laporan invalid, halaman bisa kosong, dan latest hanya tersedia pada halaman pertama. Service existing kini memvalidasi kandidat approved dalam batch 50 dengan eager loading. Hanya hasil valid yang mengisi halaman; pembacaan berhenti ketika satu hasil valid tambahan ditemukan untuk menentukan tombol Berikutnya.

Pagination tetap server-side dengan ukuran konfigurasi existing (maksimal 25), menggunakan paginator Sebelumnya/Berikutnya **tanpa total halaman spekulatif**. Ini menghindari kebutuhan membaca seluruh histori hanya untuk menghitung total valid. Memori dibatasi batch dan satu halaman hasil. Halaman jauh atau banyak record invalid tetap memerlukan pemeriksaan kandidat sebelumnya; verifikasi performanya dengan volume produksi yang representatif.

Aturan hash, signed unit identity, dua signature, versi, serta hubungan histori existing tidak diganti. Draft/pending/rejected tersaring melalui status; soft delete melalui Eloquent scope. Snapshot invalid dan unit lain tidak diterbitkan. Tidak ada pembaruan otomatis terhadap histori. Remarks tidak diganti dengan ringkasan; ringkasan ditampilkan sebagai tambahan. PDF inspeksi tetap menggunakan endpoint dan arsip PDF existing.

## Admin dan penyimpanan

Buka Master Peralatan → tombol QR → **Dokumen Standar CILT**. Pilih PDF dan simpan; bagian yang sama menyediakan preview, ganti, serta hapus dengan konfirmasi. Sukses upload/delete membuka kembali modal unit tersebut. Validasi gagal mempertahankan dokumen sebelumnya dan menampilkan pesan; browser mengharuskan file dipilih kembali. Unit tanpa CILT tetap dapat diinspeksi dan dipublikasikan. QR yang belum mempunyai unit tetap memakai alur pembuatan unit existing; konflik multi-unit tidak dipilih secara sembarang.

Migration additive `2026_10_23_000001_add_cilt_to_equipment_inspection_units.php` menambahkan lima kolom nullable pada `equipment_inspection_units`:

| Kolom | Fungsi |
| --- | --- |
| `cilt_path` | Path privat dokumen aktif, disembunyikan dari serialisasi model |
| `cilt_original_name` | Nama aman untuk tampilan |
| `cilt_size` | Ukuran byte |
| `cilt_updated_at` | Waktu penggantian/upload CILT, terpisah dari update identitas unit |
| `cilt_uploaded_by` | Admin pengunggah, FK nullable ke users |

Hanya satu slot metadata per unit sehingga tidak ada duplikasi dokumen aktif. File disimpan pada disk `local`, di `equipment-cilt/{unit_id}/{uuid}.pdf` (default root privat `storage/app/private`). Nama asli tidak dipakai sebagai path. PDF CILT tidak menjadi bagian signed snapshot, signature, PDF inspeksi, atau approval.

Upload menulis file baru dengan UUID sebelum transaksi metadata. Transaksi mengunci record unit (`lockForUpdate`), mengganti referensi, dan mencatat aksi pada unit log existing. Kegagalan transaksi membersihkan file baru dan mempertahankan metadata/file lama. Setelah commit, file sebelumnya dibersihkan. Upload bersamaan diserialkan oleh lock; perubahan terakhir menjadi dokumen aktif. Delete mengosongkan metadata dalam transaksi dan membersihkan file sesudah commit. Kegagalan cleanup dicatat untuk operator; file sisa tetap privat dan tidak dapat diakses melalui endpoint CILT. Jika proses PHP dihentikan mendadak di antara penulisan file dan commit, file privat yatim masih mungkin tertinggal dan perlu pemeriksaan operator; database dan filesystem bukan satu transaksi atomik.

Tidak ada perubahan `public_code`, `asset_code`, relasi histori, foto master, checklist, nomor dokumen, PDF final, signature, atau approval. Tidak ada seeder yang dijalankan atau menu baru. Hotfix `orWhere('id', $includeUnitId)` tetap utuh.

## Endpoint dan keamanan

| Metode | Route | Akses |
| --- | --- | --- |
| GET | `/equipment/q/{publicCode}/cilt` | Publik, public code 32 karakter valid, `public_enabled=true`, unit bukan retired |
| POST | `/admin/inspection-units/{equipmentInspectionUnit}/cilt` | Upload/replace, auth + role admin + akses menu inspeksi + CSRF |
| GET | `/admin/inspection-units/{equipmentInspectionUnit}/cilt` | Preview, auth + role admin + akses menu inspeksi |
| DELETE | `/admin/inspection-units/{equipmentInspectionUnit}/cilt` | Hapus, auth + role admin + akses menu inspeksi + CSRF |

Parameter unit Admin mengikuti route binding `public_code` existing. Tidak ada input path/file pada endpoint. Resolver publik mengikuti aturan publikasi existing, termasuk perilaku unit inactive; unit retired selalu ditolak. Menonaktifkan public access langsung menolak request dokumen berikutnya. File yang sudah diunduh pengguna tentu tidak dapat ditarik kembali.

Validasi: upload file wajib, ekstensi PDF, MIME server `application/pdf`, aturan `mimes:pdf`, maksimum 10 MB, dan header `%PDF-`. Ini bukan antivirus atau sanitasi seluruh struktur PDF; hanya Admin berwenang yang boleh mengunggah dokumen standar. Penyimpanan selalu privat; path dibatasi folder unit dan nama internal UUID. Nama yang ditampilkan di-escape Blade. Stream response memakai `application/pdf`, `inline`, `nosniff`, `private, no-store`, no-index, dan no-referrer. Stream dibuka sebelum pengiriman agar penggantian path serentak tidak mengalihkan isi response. Route publik mempertahankan throttle 30/menit existing. Tidak ada token atau filesystem path dalam log CILT.

## File source

Baru:

- `app/Http/Requests/Admin/SaveEquipmentCiltRequest.php`: authorization dan validasi PDF.
- `app/Services/Inspector/EquipmentCiltService.php`: storage, transaksi, cleanup, metadata, stream PDF.
- `database/migrations/2026_10_23_000001_add_cilt_to_equipment_inspection_units.php`: lima kolom opsional.
- `resources/views/admin/inspection-forms/partials/cilt-document.blade.php`: pengelolaan CILT di modal QR.
- `resources/views/public/partials/equipment-checklist.blade.php`: kategori, hasil, remarks snapshot.
- `resources/views/public/partials/equipment-counts.blade.php`: jumlah A/B/C.
- `resources/views/public/partials/equipment-icon.blade.php`: ikon SVG navigasi/dokumen.
- `tests/Feature/EquipmentCiltTest.php`: authorization, lifecycle file, rollback, validasi, akses publik, path/unit isolation.
- `docs/equipment-qr-cilt.md`: panduan ini.

Diubah:

- `app/Models/EquipmentInspectionUnit.php`: casts metadata dan hidden path.
- `app/Http/Controllers/Admin/EquipmentInspectionFormController.php`: metadata CILT pada QR modal.
- `app/Http/Controllers/Admin/EquipmentInspectionUnitController.php`: upload, preview, hapus CILT.
- `app/Http/Controllers/PublicEquipmentInspectionController.php`: metadata dan route PDF CILT melalui resolver publik existing.
- `app/Services/Inspector/PublicEquipmentHistoryService.php`: pagination hasil valid, latest independen halaman, kategori dan nama Inspektor snapshot.
- `resources/views/public/equipment-history.blade.php`: tiga Tab Card responsive.
- `resources/views/admin/inspection-forms/partials/qr-modal.blade.php`: memasukkan partial CILT.
- `routes/web.php`: empat route CILT dengan middleware existing.
- `tests/Feature/EquipmentInspectionUnitQrTest.php`: regression pagination valid, latest halaman kedua, snapshot name, serta signature tidak berubah setelah upload CILT.

## Pemeriksaan dan deployment manual

Status saat implementasi: `git diff --check` lulus. Pemeriksaan statis memastikan empat referensi partial Blade dan empat action controller route CILT tersedia. State Alpine berhasil diparse dan enam transisi keyboard (kanan/kiri termasuk wrap, Home, End) lulus pemeriksaan JavaScript terisolasi; ini bukan browser test. PHP, Node, dan direktori vendor tidak tersedia pada environment pengerjaan. PHP lint, kompilasi Blade, Artisan route:list, Pint, feature tests, migration, browser/mobile preview, dan pengujian upload konkuren **NOT RUN**. Test source tersedia, bukan klaim runtime Laravel PASS.

Deployment cPanel oleh operator setelah review:

1. Backup database dan file privat. Pastikan PHP 8.2+, dependency existing, akses tulis storage privat, serta `APP_DEBUG=false` dan HTTPS produksi.
2. Validasi di staging dengan database terisolasi. Pastikan CLI PHP memakai konfigurasi yang benar. File `phpunit.xml` menggunakan SQLite `:memory:`; jangan menimpanya dengan konfigurasi database produksi.
3. Deploy source melalui prosedur tim. Tidak ada perubahan composer/package, dependency baru, atau entry bundler. Pastikan asset Livewire existing tersedia, termasuk jika instalasi menggunakan asset yang dipublikasikan. Ikuti strategi cache route/view tim; jangan membiarkan route cache lama menyembunyikan endpoint baru.
4. Jalankan hanya migration baru setelah backup/review (perintah berikut **belum dijalankan**):

   ```bash
   php artisan migrate --path=database/migrations/2026_10_23_000001_add_cilt_to_equipment_inspection_units.php --force
   ```

5. Pada MultiPHP INI Editor, sediakan `upload_max_filesize` minimal 10M dan `post_max_size` lebih besar (misalnya 12M atau 16M) untuk overhead multipart; periksa juga batas request web server. Jangan membuka folder privat melalui symlink publik.
6. Smoke test di staging: tiga tab dan keyboard/mobile, latest pada halaman kedua, histori invalid/draft/soft-delete tidak muncul, PDF inspeksi asli, upload/preview/replace/delete CILT, invalid PDF, file hilang, QR publik nonaktif/retired, serta dua Admin mengganti dokumen bersamaan. Pastikan QR dan signature lama tetap sama.

Perintah targeted opsional di environment testing terisolasi, bukan produksi (belum dijalankan):

```bash
php artisan test --filter=EquipmentCiltTest
php artisan test --filter=EquipmentInspectionUnitQrTest
```

Test CILT menggunakan `DatabaseMigrations` agar cleanup setelah commit benar-benar dapat diuji; perintah ini menjalankan migration pada database test. Jangan jalankan jika isolasi database belum dipastikan. Tidak ada build, cache clear, migration, seeder, git pull, commit/push, atau deployment yang dijalankan selama pengerjaan source.
