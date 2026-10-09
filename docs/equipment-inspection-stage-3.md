# Inspeksi Peralatan — Tahap 3

Implementasi source saja. Tidak ada migration, build, lint PHP/Blade, formatter, test, browser test, deployment, commit, merge, atau push yang dijalankan oleh agent dalam task ini. Pekerjaan berada di main sesuai instruksi pengguna. Review statis dan pemeriksaan whitespace diff tidak membuktikan kelulusan runtime.

## Alur

- TTD Inspektor dan penomoran Tahap 2 tetap satu transaksi. Setelah commit, hanya tahap Manager Workshop dibuat pada tabel approval terpisah; signature hanya dibuat saat tanda tangan benar-benar diberikan. TPM / Leader Gugus dinonaktifkan.
- Resolver memakai seksi Machine Workshop di unit Workshop. Struktur harus unik, akun Manager harus ber-role approver dan memiliki email valid. Tidak ada fallback ke SM Workshop atau perubahan role otomatis. Konfigurasi tidak valid mempertahankan ready_for_approval, nomor/TTD, dan pesan yang dapat dipulihkan Admin. Konfigurasi TPM tidak diperlukan.
- Status baru: draft → ready_for_approval → pending_manager → approved. Manager dapat mengembalikan menjadi revision_required dengan alasan wajib.
- Token acak 64 karakter disimpan hash + encrypted, berlaku tujuh hari. Semua route memakai auth, role, serta signer/pemilik/menu check backend. Link selesai/kedaluwarsa/versi lama tidak menyediakan halaman approval. Retry POST keputusan identik hanya memberikan receipt tanpa tindakan kedua, dan hanya selama token/versi masih cocok.
- Email memakai ApprovalNotificationService dan template approval WOMS, hanya kepada Manager Workshop aktif. Email otomatis/resend, inbox, dan link approval TPM / Leader Gugus lama diblokir oleh query aktif. Inspeksi tidak menyertakan password bawaan; perilaku email modul lain tetap. Tidak ada email Inspektor.
- Email diklaim dalam transaksi (attempt UUID), dikirim after-commit, kemudian hasil transport dicatat. Satu aktivasi hanya satu percobaan otomatis. Admin boleh resend step aktif; cooldown satu menit, atau sepuluh menit untuk proses sending yang belum terkonfirmasi. Token expired diperbarui tanpa mengganti signer/nomor/step.
- Mail tidak memiliki jaminan exactly-once lintas database dan transport: jika proses mati sesudah transport menerima email tetapi sebelum hasil tercatat, status sending tetap terlihat. Admin harus memeriksa transport sebelum resend manual. Terkirim bukan berarti diterima/dibaca.
- Persetujuan menggunakan UX pointer canvas, PNG upload, Pakai TTD Terakhir (resolver full signature WOMS), dan visual helper approval existing. Penyimpanan hasil tetap privat dengan validasi PNG/ukuran/coretan; tidak memakai writer signature publik.
- Pengembalian mengunci versi lama. POST Perbaiki Laporan membuka draft document_version + 1 dengan nomor tetap. Nomor/sequence tidak diterbitkan ulang. Semua signed_payload/hash dan signature lama tetap; foto yang dilepas pada revisi hanya soft-delete metadata dan tidak dihapus fisik bila nomor sudah terbit.
- Snapshot versi ditandatangani menjadi sumber PDF; lampiran historis dicari termasuk soft-deleted dengan pemeriksaan hash. Versi lama dilabeli HISTORI/REVISI dan tidak menjadi persetujuan aktif. Versi revisi aktif tidak menampilkan tanda tangan yang dikembalikan. Versi draft baru dimulai tanpa tanda tangan.
- Setelah dua signature (Inspektor dan Manager Workshop) versi sama lengkap, approval committed dahulu, lalu arsip PDF A4 privat dengan checksum dan nama unik dibuat. Kegagalan arsip tidak mengubah approval. Admin dapat mencoba arsip ulang bila path belum tersimpan. Path final yang sudah tersimpan tidak pernah ditimpa; kehilangan file final harus dipulihkan dari backup, bukan regenerasi diam-diam.
- Satu template PDF existing dipertahankan. Dua logo SIG/Semen Tonasa, dua tanda tangan Inspektor/Manager, tanpa kolom TPM / Leader Gugus. Penanggung Jawab dari pengisi form. PDF final lama yang sudah diarsipkan tetap utuh.
- Laporan lama berstatus pending_manager selesai setelah Manager menandatangani; tahap Leader yang belum diputuskan dibatalkan dengan audit. Untuk pending_leader, Admin menekan Finalisasi dengan Dua Tanda Tangan melalui recovery existing. Transaksi memeriksa snapshot dan persetujuan Manager, membatalkan tahap Leader yang belum diputuskan, mencabut token, lalu membuat arsip. Tidak ada perubahan database otomatis ketika halaman dibuka; signature/keputusan historis tetap tersimpan. Tidak ada migration baru.

## Admin dan integrasi

- Inspeksi merupakan menu support mandiri tepat sebelum Master Data; Admin non-Super Admin perlu grant inspeksi melalui Access Control existing. Tidak ada auto-grant.
- Tiga tab pada Admin dan Inspeksi Saya: Perlu Tindakan (draft, perlu revisi, gagal/belum inisialisasi, finalisasi alur lama), Proses Approval (pending_manager), Riwayat (approved/full approval). Kelompok saling terpisah; tab Selesai dihapus dan URL completed lama dipetakan ke Riwayat. Count tab adalah total sebelum pencarian; Inspektor hanya menghitung miliknya.
- Badge sidebar Inspeksi memakai query Perlu Tindakan yang sama, bukan status sudah dibaca. Detail/Remarks/PDF tidak menulis read receipt; tabel read lama dipertahankan tanpa perubahan data.
- Gaya tab, angka badge, dan pencarian ringkas mengikuti HPP. Pencarian mencakup nomor dokumen/peralatan/inspektor. Ikon info membuka modal dua tahap dengan nama, jumlah TTD/persentase, status, waktu, catatan dan status email. Kirim ulang hanya tersedia untuk Manager aktif melalui authorization existing; endpoint JSON tidak mengekspos token/signature path.
- Tombol hapus tersedia pada daftar Admin, detail Admin, dan Inspeksi Saya. Hanya Admin dengan akses inspeksi atau Inspektor pemilik yang boleh menghapus laporan nonfinal. Hapus memakai soft delete, transaction, lockForUpdate dan pemeriksaan lock_version; permintaan pending/locked dibatalkan dan token dicabut. Nomor, foto, signature, snapshot dan audit tetap tersimpan. Laporan approved atau mempunyai final_pdf_path tidak dapat dihapus. Tidak ada restore otomatis atau hard delete.
- Monitoring dipaginasi; Remarks hanya satu tombol kuning, jumlah item remark non-whitespace termasuk A. Modal read-only memuat foto privat dan semua nilai A/B/C.
- Admin melihat dua tahap aktif, histori keputusan/alasan revisi, signature per versi, PDF historis, audit paginated, status email, recovery inisialisasi/finalisasi alur lama/arsip, dan resend Manager. Admin tidak bisa menyetujui atas nama signer.
- ApprovalDocumentInbox menyediakan tipe equipment_inspection, filter, pending count/preview/open route yang memakai query aktif dan signer yang sama.
- User Panel diberi pemeriksaan tambahan khusus request Inspeksi pending/locked untuk melindungi penghapusan akun. Admin tidak dapat mengubah role signer yang masih mempunyai permintaan Inspeksi aktif.
- Tidak mengubah rule HPP/BAST/QC/Order/AppSheet. Perubahan titik integrasi bersama bersifat tambahan: registry, badge, inbox, email opt-out password, dan perlindungan akun.

## File baru

- `app/Http/Controllers/Admin/EquipmentInspectionController.php`
- `app/Http/Controllers/Approval/EquipmentInspectionController.php`
- `app/Http/Requests/Inspector/DecideEquipmentInspectionRequest.php`
- `app/Models/EquipmentInspectionApproval.php`
- `app/Services/Inspector/EquipmentInspectionPdfService.php`
- `app/Services/Inspector/EquipmentInspectionWorkflow.php`
- `app/Support/Inspector/EquipmentInspectionApproverResolver.php`
- `app/Support/Inspector/EquipmentInspectionIndexTabs.php`
- `database/migrations/2026_10_09_000002_add_equipment_inspection_approval_workflow.php`
- `resources/views/admin/inspections/index.blade.php`
- `resources/views/admin/inspections/show.blade.php`
- `resources/views/approval/equipment-inspection.blade.php`
- `resources/views/approval/partials/equipment-inspection-signature-script.blade.php`
- `docs/equipment-inspection-stage-3.md`

## File existing diubah

- `app/Http/Controllers/Admin/UserPanelController.php`
- `app/Http/Controllers/Inspector/EquipmentInspectionController.php`
- `app/Http/Controllers/Pkm/UserPanelController.php`
- `app/Models/EquipmentInspection.php`
- `app/Notifications/ApprovalRequestedNotification.php`
- `app/Policies/EquipmentInspectionPolicy.php`
- `app/Services/Approvals/ApprovalNotificationService.php`
- `app/Services/Inspector/EquipmentInspectionService.php`
- `app/Support/AdminActionCenter.php`
- `app/Support/AdminMenuRegistry.php`
- `app/Support/ApprovalDocumentInbox.php`
- `app/Support/Inspector/EquipmentInspectionPdfPresenter.php`
- `resources/views/approval-documents/index.blade.php`
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/emails/approval/requested-text.blade.php`
- `resources/views/emails/approval/requested.blade.php`
- `resources/views/inspector/equipment-forms/show.blade.php`
- `resources/views/inspector/inspections/index.blade.php`
- `resources/views/inspector/inspections/pdf.blade.php`
- `routes/web.php`

## Verifikasi manual sebelum penerapan

1. Review migration additive baru, backup, lalu jalankan di environment yang disetujui sebelum melayani source baru. Migration Tahap 2 tidak diedit. Route badge/inbox baru membutuhkan tabel baru; jangan menjalankan source dengan schema lama. Jangan rollback migration yang sudah berisi histori approval tanpa rencana pelestarian audit.
2. Pastikan role/email Manager dan relasi Machine Workshop / Workshop tidak ambigu. Uji konfigurasi kosong/ganda/salah role: TTD/nomor tetap, Admin menerima pesan dan dapat retry. Hilangnya TPM Officer tidak boleh menghalangi alur baru.
3. Grant menu inspeksi ke subrole Admin yang sesuai. Uji guest, inspector lain, approver lain, Admin tanpa grant, Super Admin; cek detail, PDF, foto, remarks, sign/return/revise/resend/recover.
4. Uji alur dua signature: hanya Manager menerima email; setelah Manager, status approved dan arsip dibuat. Pastikan link/inbox/resend Leader lama tidak aktif. Uji recovery pending_leader dua kali: signature tetap, token Leader dicabut, final PDF tidak ditimpa. Periksa isi email tidak mengandung password dan token tidak tercatat di log.
5. Uji double submit, dua tab, token lama/kedaluwarsa, return tanpa alasan, perubahan payload versi/role/owner, dan resend bersamaan. Nomor tetap saat retry/revisi dan urutan approval tidak terlewati.
6. Uji gagal mail, konfigurasi transport, proses sending tidak terkonfirmasi, cooldown resend, pembaruan token. Uji gagal render/storage PDF: approval tetap sah, pesan arsip terlihat, retry Admin tanpa menimpa final.
7. Uji revisi oleh Manager, tanggal/checklist/remark/foto berubah, tanda tangan baru dua tahap, PDF historis/aktif, foto lama tetap tersedia, serta audit perubahan. Kondisi C boleh disetujui tanpa menghapus temuan/menyatakan alat layak.
8. Uji draft/revisi masuk Perlu Tindakan, pending_manager hanya Proses Approval, approved hanya Riwayat; badge sidebar sama dengan Perlu Tindakan. Pencarian dan pagination mempertahankan tab. Count Inspektor hanya laporan miliknya. Uji akses modal info Admin, hitungan versi aktif, tidak ada token di JSON, dan resend tidak tersedia setelah selesai/dihapus.
9. Review PDF semua 10 form dan remark panjang/foto banyak; layout dua signature, logo, nomor, status Draft/Revisi/Final, checksum serta hasil arsip tidak berubah setelah data akun/organisasi berubah.
10. Jalankan build aset, pemeriksaan PHP/Blade, targeted regression, dan browser/mobile/canvas secara terpisah. Tidak ada hasil runtime yang diklaim di task ini.

## Batas pemulihan

Tidak ada reassignment approver, rollback signature, edit final, hard delete laporan, restore otomatis, atau resend oleh Inspektor. Perubahan organisasi hanya memengaruhi chain baru; chain berjalan mempertahankan signer snapshot. Inisialisasi laporan ready lama harus lewat tindakan recovery Admin, bukan side effect GET atau migrasi data otomatis.

## Pembaruan monitoring dan hapus laporan

- Migration tambahan: `2026_10_09_000003_add_soft_deletes_to_equipment_inspections.php`, menambahkan `deleted_at`. Jalankan migration ini sebelum source dengan SoftDeletes dipakai; migration tidak dijalankan oleh agent. Jangan rollback kolom sebelum memeriksa laporan terhapus karena laporan tersebut dapat tampil kembali.
- File baru: `EquipmentInspectionDeletionService`, komponen Blade `inspections/index-tabs` dan `inspections/delete-button`, modal `admin/inspections/_approval-modal`, serta `tests/Feature/EquipmentInspectionManagementTest.php`.
- File diubah: model/policy inspeksi, `EquipmentInspectionIndexTabs`, `AdminActionCenter`, controller Inspeksi Admin/Inspektor, workflow (audit hasil email saat penghapusan konkuren), routes, daftar Admin/Inspektor dan detail Admin.
- Regression test disiapkan untuk kelompok tab/badge, pembatasan pemilik, penghapusan pending dengan pelestarian audit/media, larangan hapus final, stale version, dan JSON modal tanpa token. Test, lint PHP/Blade, formatter, migration, build dan browser check belum dijalankan sesuai batasan pengguna; review statis/diff bukan bukti runtime lulus.
- Verifikasi setelah penerapan: hapus draft dan laporan pending dari kedua panel; pastikan count turun, link approval lama tidak aktif, file/snapshot tetap tersimpan, dan laporan final tetap terlindungi. Periksa modal serta pencarian pada desktop/mobile. Build frontend diperlukan saat deployment karena class Blade berubah; agent tidak menjalankannya.
