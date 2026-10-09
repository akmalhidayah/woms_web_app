# Inspeksi Peralatan — Tahap 2

Implementasi kode untuk draft, foto, tanda tangan Inspektor, nomor dokumen, dan preview PDF. Migration, build, formatter, deployment, serta pengujian runtime tidak dijalankan dalam task ini.

## Perilaku

- Daftar Form selalu membuka pemeriksaan baru tanpa menulis database. Penyimpanan pertama membuat record dan snapshot katalog; draft dilanjutkan melalui Inspeksi Saya.
- Pemilik berasal dari akun login ber-role `inspector`. Policy memeriksa pemilik untuk detail, update, PDF, foto, dan tanda tangan. Route tetap berada dalam `auth` dan `role:inspector`.
- Katalog existing `app/Support/Inspector/EquipmentFormCatalog.php` tetap 10 form/73 item. Template, nama, kelompok, label, dan urutan item disalin saat draft dibuat.
- Draft boleh belum lengkap. Sebelum TTD, semua item harus A/B/C dan remark B/C tidak boleh kosong/whitespace. Tanggal pemeriksaan tidak boleh melebihi hari ini dalam `config('app.timezone')`.
- `lock_version` bertambah pada penyimpanan/TTD untuk menolak tab usang. `document_version` terpisah untuk versi dokumen yang ditandatangani; tahap ini hanya versi 1 dan belum memiliki alur revisi.
- Foto baru hanya pada B/C: maksimum 3 foto/item, 2 MB/foto, total upload 10 MB/penyimpanan, dimensi maksimum 4096 × 4096 dan 12 megapiksel. JPG/PNG/WebP diperiksa lalu di-decode/re-encode JPEG maksimum sisi 1600 piksel. Rating berubah ke A tidak menghapus remark/foto tersimpan.
- Penghapusan foto bersifat eksplisit dengan konfirmasi. Metadata di-soft-delete dalam transaksi, file dibersihkan setelah commit; kegagalan cleanup dilaporkan ke log. File baru dibersihkan jika transaksi gagal.
- Media memakai disk `local` privat. Foto dan gambar TTD hanya melalui endpoint terotorisasi; path internal tidak dikembalikan ke client.
- Canvas memakai pointer events, koordinat kanvas tetap, preview, dan konfirmasi. Server menolak PNG kosong/terlalu sedikit coretan, memeriksa kembali data, lalu mengunci laporan setelah TTD.
- TTD, increment counter tahunan, nomor, signature, status, dan audit berada dalam satu transaksi. Counter diinisialisasi lewat upsert yang hanya memperbarui key tahun saat konflik (sequence tidak direset), kemudian dibaca dengan `lockForUpdate`; unique constraints menjaga nomor dan satu signature per peran/versi. Pengiriman ulang tanda tangan mengembalikan hasil yang sudah ada.
- Format nomor `NNN/INSP/25.10/MM-YYYY` memakai waktu server saat penerbitan dalam timezone aplikasi, terpisah dari `inspection_date`. Sequence minimal tiga digit, lintas peralatan dan bulan, reset per tahun.
- Signature menyimpan payload lengkap versi yang ditandatangani dan hash konten, termasuk hash foto. Hash menggunakan urutan key objek yang dinormalisasi agar stabil setelah penyimpanan JSON MySQL; urutan daftar tetap dipertahankan.
- PDF dibangun saat diminta dari record tertentu, tanpa write/status transition atau penyimpanan PDF final. Draft ber-watermark DRAFT, jawaban kosong tetap kosong. Setelah TTD, status Siap Approval/belum final; kolom Manager/Leader tetap kosong.

## Database

Migration `2026_10_09_000001_create_equipment_inspection_tables.php` membuat enam tabel: inspections, answers, attachments, signatures, logs, dan counters, masing-masing dengan prefix `equipment_inspection` sesuai nama model. Histori signature menyimpan payload per versi; tahap berikutnya harus mempertahankan payload dan media versi lama ketika membuat revisi baru, bukan menimpa data audit atau memakai kembali tanda tangan.

## Referensi Excel

Workbook `!CHECKLIST 2026.xlsx` dibaca langsung dari lampiran menggunakan struktur XML workbook: merged cells, ukuran kolom, nilai sel, dan pengaturan cetak. Worksheet peralatan memakai A4 portrait, dengan dua blok untuk cetak. PDF memakai satu blok laporan, Activity + Description A/B/C + Remark, Task Record, identitas Seksi/Unit, dua kolom approval kanan atas, dan Penanggung Jawab/Paraf/Hari-Tanggal di bawah. Font PDF mengikuti DejaVu Sans yang dipakai DomPDF WOMS.

Perbedaan yang dipertahankan tanpa mengubah katalog:

- Mesin Potong: blok pertama C11–C17 memuat 7 item termasuk compressor; blok kedua C34–C39 hanya 6 item tanpa compressor. Master tetap 7 item.
- Mesin Bor: blok pertama memakai 2Y 4132 dan BS-40; blok kedua 2Y 4132 dan ZJ4132. Master tetap 2Y 4132 + BS-40, masing-masing 3 item. Ejaan Excel `On/Of` tidak mengganti label master `On/Off`.
- Plasma dan Forklift memiliki nomor urut tercetak berulang. PDF menggunakan posisi berurutan dari snapshot, tanpa menambah item dari blok duplikat.
- Header Excel mencantumkan Leader SGA dan nama pejabat lama. PDF mengikuti ketentuan terbaru Manager Machine Workshop dan Leader Gugus/Senior Manager TPM; nama tidak disalin dari Excel. Nama/jabatan hanya muncul dari snapshot signature ketika penandatangan benar-benar menandatangani.

## File baru

- `database/migrations/2026_10_09_000001_create_equipment_inspection_tables.php`
- `app/Models/EquipmentInspection.php`, `EquipmentInspectionAnswer.php`, `EquipmentInspectionAttachment.php`, `EquipmentInspectionSignature.php`, `EquipmentInspectionLog.php`
- `app/Policies/EquipmentInspectionPolicy.php`
- `app/Http/Requests/Inspector/SaveEquipmentInspectionRequest.php`, `SignEquipmentInspectionRequest.php`
- `app/Http/Controllers/Inspector/EquipmentInspectionController.php`
- `app/Services/Inspector/EquipmentInspectionService.php`
- `app/Support/Inspector/EquipmentInspectionNumberGenerator.php`, `EquipmentInspectionSnapshot.php`, `InspectionImageStorage.php`, `EquipmentInspectionViewData.php`, `EquipmentInspectionPdfPresenter.php`
- `resources/views/inspector/equipment-forms/_item.blade.php`, `_script.blade.php`, `_signature.blade.php`
- `resources/views/inspector/inspections/index.blade.php`, `pdf.blade.php`
- Dokumen ini.

File existing yang diubah: controller daftar form menggunakan presenter untuk state awal; layout Inspektor menambah navigasi Inspeksi Saya; daftar form mengganti penjelasan simulasi; detail form terhubung penyimpanan/TTD dengan kartu mobile dan tabel/grid desktop; `routes/web.php` menambah endpoint inspeksi terotorisasi. Modul Order, HPP, BAST, QC, AppSheet, dan approval existing tidak diubah.

## Verifikasi manual terpisah

1. Review dan jalankan migration di lingkungan yang disetujui; periksa unique index, foreign key, dan counter tahunan. Build asset frontend pada proses deployment terpisah.
2. Pastikan GD dan dukungan JPEG/PNG/WebP tersedia, disk privat dapat ditulis, serta batas upload PHP/web server cukup untuk payload 10 MB. Periksa juga `max_file_uploads`, batas request, dan memori DomPDF untuk banyak foto.
3. Buat beberapa laporan pada form/tanggal sama; pastikan record berbeda. Simpan sebagian, logout/login, lanjutkan draft yang sama. Perubahan katalog setelah itu tidak boleh mengubah snapshot lama.
4. Coba dua tab: simpan tab pertama, lalu simpan/tandatangani tab usang. Tab usang harus ditolak. Uji dua tanda tangan bersamaan termasuk laporan berbeda pada awal tahun: nomor unik, reset tahunan, tidak ada duplikasi saat retry.
5. Uji tanggal masa depan, B/C tanpa remark/spasi, nilai tidak sah, key item palsu, owner palsu, dan lampiran milik laporan lain. Uji tanda tangan kosong serta checklist belum lengkap.
6. Uji unggah/hapus foto, perubahan B/C ke A, file palsu/oversize, serta kegagalan storage/rollback database. Foto lama tidak hilang kecuali dihapus eksplisit; metadata audit tetap ada.
7. Uji akses guest, akun non-inspector, dan inspector lain terhadap seluruh detail/write/PDF/media; setelah TTD, percobaan update foto/tanggal/checklist harus ditolak.
8. Preview PDF semua 10 peralatan: draft kosong/sebagian, signed, remark panjang/multibaris, foto banyak, dan tanpa foto. Periksa centang, A4 portrait, page break, tidak ada halaman kosong, serta TTD pada posisi bawah.
9. Uji kanvas mouse dan touch, tampilan HP/tablet/desktop, reset, status perubahan belum tersimpan, dan filter riwayat. Tanggal pemeriksaan mundur harus berbeda dari tanggal penerbitan/TTD pada PDF.

Belum aktif: email, approval Manager Workshop/Leader Gugus, revisi signed, rollback signature, dan arsip PDF final. Tidak ada endpoint untuk menghapus histori laporan.
