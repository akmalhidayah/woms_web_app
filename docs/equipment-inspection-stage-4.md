# Inspeksi Peralatan — Tahap 4

Tahap ini menambahkan identitas unit fisik, QR permanen, dan histori inspeksi publik di atas Dynamic Master Form dan workflow dua tanda tangan yang sudah ada. Implementasi bersifat additive; tabel, signature, hash, PDF final, nomor dokumen, dan approval historis tidak dimigrasikan ulang.

## Arsitektur dan data

- `equipment_inspection_units` menyimpan unit fisik. Satu Master Form dapat mempunyai banyak unit. `asset_code` dan `public_code` unik secara global.
- `equipment_inspection_unit_links` menghubungkan tepat satu dokumen ke satu unit. Unique constraint pada `equipment_inspection_id` mencegah satu dokumen masuk ke dua unit.
- Link `direct` dibuat oleh Inspector sebelum tanda tangan. Snapshot ID/kode/nama unit masuk ke signed payload tanpa mengubah `template_snapshot`.
- Link `admin_legacy` dibuat Admin untuk dokumen lama. Link ini sengaja tidak dimasukkan ke payload lama sehingga hash/signature lama tetap valid.
- `equipment_inspection_unit_logs` serta event baru pada log inspeksi mencatat create/update, publikasi, retire, rotasi QR, direct link, dan mapping legacy.
- Unit tidak memiliki endpoint delete. Gunakan `inactive` atau `retired`; retire juga menonaktifkan akses publik.

## Rollout pemilihan unit

Konfigurasi deployment:

```dotenv
EQUIPMENT_INSPECTION_REQUIRE_UNIT=false
EQUIPMENT_INSPECTION_PUBLIC_BASE_URL=https://woms.example.com
```

Urutan aman:

1. Jalankan migration additive setelah backup dan review.
2. Biarkan `EQUIPMENT_INSPECTION_REQUIRE_UNIT=false`.
3. Admin mendaftarkan unit fisik yang benar pada **Inspeksi Peralatan → Master Unit Peralatan**.
4. Verifikasi hubungan Master Form, kode aset, foto, dan lokasi.
5. Mapping dokumen lama dilakukan manual jika diperlukan.
6. Setelah katalog unit siap, ubah `EQUIPMENT_INSPECTION_REQUIRE_UNIT=true` melalui proses konfigurasi deployment resmi.

Saat flag masih `false`, laporan baru boleh tanpa unit agar deployment awal tidak memblokir Inspector. Setelah flag `true`, laporan baru wajib memilih unit aktif dari Master Form yang sama. Draft lama tanpa unit tetap dapat dibuka dan dilanjutkan. Identitas unit yang pernah ditandatangani tidak dapat diganti melalui form biasa.

## Route

Admin, dilindungi `auth`, `role:admin`, dan `admin_menu:inspeksi`:

- `GET /admin/inspection-units`
- `GET|POST /admin/inspection-units/create|/admin/inspection-units`
- `GET|PUT /admin/inspection-units/{unit}|/admin/inspection-units/{unit}`
- `PATCH /admin/inspection-units/{unit}/public-access`
- `PATCH /admin/inspection-units/{unit}/retire`
- `POST /admin/inspection-units/{unit}/rotate-public-code`
- `POST /admin/inspection-units/{unit}/legacy-links`
- `GET /admin/inspection-units/{unit}/image`
- `GET /admin/inspection-units/{unit}/qr.svg`
- `GET /admin/inspection-units/{unit}/label`

Publik, read-only, tanpa login, rate limit 30 request/menit:

- `GET /equipment/q/{public_code}`
- `GET /equipment/q/{public_code}/image`

QR memakai encoder SVG internal, tidak mengirim URL ke layanan eksternal, tidak berisi token autentikasi/approval, dan mengambil base URL dari konfigurasi. Production wajib menggunakan HTTPS. URL yang sangat panjang harus dipendekkan melalui `EQUIPMENT_INSPECTION_PUBLIC_BASE_URL` agar tetap dalam kapasitas encoder QR versi 1–6.

## Proyeksi publik

Halaman publik hanya memproyeksikan:

- public code, kode aset, nama, lokasi umum, status administratif, dan foto unit/form;
- tanggal, nomor dokumen, label checklist, rating A/B/C, hitungan, dan ringkasan deterministik dari signed payload versi aktif.

Laporan harus berstatus `approved`, tidak soft-deleted, serta memiliki signature Inspector dan Manager versi aktif dengan hash payload yang valid dan sama. Draft, pending, revision, dokumen unit lain, remark mentah, foto bukti, signature, akun, email, token, PDF, audit log, dan storage path tidak diproyeksikan.

`public_enabled=false`, unit retired, kode lama setelah rotasi, dan kode invalid menghasilkan 404 yang sama. Endpoint gambar khusus hanya membaca foto unit atau fallback foto Master Form yang terkait dengan unit terpublikasi. Mengaktifkan publikasi berarti Admin menyetujui identitas umum dan foto unit/form tersebut untuk tampil pada halaman QR.

## Mapping legacy

Pada detail unit, Admin memilih dokumen belum terhubung dengan Master Form yang sama. Mapping:

- tidak mengubah `template_snapshot`, `template_hash`, `document_no`, checklist, signature, atau PDF;
- menolak dokumen yang sudah terkait ke unit lain;
- mencatat aktor, waktu, unit, sumber `admin_legacy`, dan alasan opsional;
- membuat laporan approved yang valid tersedia di histori publik bila unit dipublikasikan.

Koreksi mapping lintas unit tidak disediakan melalui mapping biasa. Jika terjadi salah identitas setelah signed, lakukan prosedur administratif terkontrol sesuai policy perusahaan; jangan mengedit payload atau signature.

## Operasional Admin dan Inspector

Admin membuat unit, memilih Master Form, mengisi kode/nama/lokasi, dan dapat mengunggah foto khusus unit. Jika foto unit kosong, halaman memakai foto Master Form; jika keduanya kosong, placeholder tampil. Admin dapat preview/download SVG, mencetak label 70 × 50 mm, menyalin URL, disable/enable publikasi, retire, dan melakukan rotasi eksplisit. Setelah rotasi, label lama wajib diganti.

Inspector tetap memakai alur lama: pilih form, pilih unit yang tersedia, isi A/B/C, simpan draft, tanda tangan, lalu Manager Workshop approve/return. Workflow, email, token, revisi, arsip PDF, hak hapus, dan jumlah tanda tangan tidak berubah.

## Deployment dan verifikasi

Migration baru:

`2026_10_20_000002_create_equipment_inspection_unit_tables.php`

Jangan menjalankan migration pada database produksi tanpa backup dan review. Setelah deployment, lakukan smoke test:

1. Buat unit pada dua Master Form dan pastikan unit silang ditolak Inspector.
2. Buat draft, ganti unit sebelum TTD, lalu pastikan unit terkunci setelah TTD.
3. Approve laporan dan scan QR dari perangkat tanpa login.
4. Pastikan hanya laporan approved unit tersebut yang tampil.
5. Disable QR dan pastikan URL tidak membuka histori; aktifkan kembali jika sesuai.
6. Rotasi kode, pastikan URL lama 404, lalu cetak ulang label.
7. Mapping satu dokumen lama dan bandingkan hash, nomor, signature, serta PDF sebelum/sesudah.
8. Uji foto unit, fallback foto Master Form, placeholder, mobile layout, dan print 70 × 50 mm.

Source Tahap 4 menambahkan automated tests, tetapi hasil runtime harus dicatat dari environment yang memiliki PHP, dependency Composer, GD, dan database test terisolasi. Tahap ini tidak membutuhkan perubahan dependency Composer.
