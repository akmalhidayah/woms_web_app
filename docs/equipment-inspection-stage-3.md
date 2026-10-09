# Inspeksi Peralatan — Tahap 3

Implementasi source saja. Tidak ada migration, build, lint PHP/Blade, formatter, test, browser test, deployment, commit, merge, atau push yang dijalankan oleh agent dalam task ini. Pekerjaan berada di main sesuai instruksi pengguna. Review statis dan pemeriksaan whitespace diff tidak membuktikan kelulusan runtime.

## Alur

- TTD Inspektor dan penomoran Tahap 2 tetap satu transaksi. Setelah commit, chain Manager lalu Leader dibuat pada tabel approval terpisah; signature hanya dibuat saat tanda tangan benar-benar diberikan.
- Resolver memakai seksi Machine Workshop di unit Workshop dan seniorManager unit TPM Officer. Struktur harus unik, role kedua akun harus approver, email valid. Tidak ada fallback ke SM Workshop atau perubahan role otomatis. Konfigurasi tidak valid mempertahankan ready_for_approval, nomor/TTD, dan pesan yang dapat dipulihkan Admin.
- Status: draft → ready_for_approval → pending_manager → pending_leader → approved. Manager/Leader dapat mengembalikan menjadi revision_required dengan alasan wajib.
- Token acak 64 karakter disimpan hash + encrypted, berlaku tujuh hari. Semua route memakai auth, role, serta signer/pemilik/menu check backend. Link selesai/kedaluwarsa/versi lama tidak menyediakan halaman approval. Retry POST keputusan identik hanya memberikan receipt tanpa tindakan kedua, dan hanya selama token/versi masih cocok.
- Email memakai ApprovalNotificationService dan template approval WOMS. Inspeksi secara eksplisit tidak menyertakan password bawaan; perilaku email modul lain tetap. Tidak ada email Inspektor.
- Email diklaim dalam transaksi (attempt UUID), dikirim after-commit, kemudian hasil transport dicatat. Satu aktivasi hanya satu percobaan otomatis. Admin boleh resend step aktif; cooldown satu menit, atau sepuluh menit untuk proses sending yang belum terkonfirmasi. Token expired diperbarui tanpa mengganti signer/nomor/step.
- Mail tidak memiliki jaminan exactly-once lintas database dan transport: jika proses mati sesudah transport menerima email tetapi sebelum hasil tercatat, status sending tetap terlihat. Admin harus memeriksa transport sebelum resend manual. Terkirim bukan berarti diterima/dibaca.
- Persetujuan menggunakan UX pointer canvas, PNG upload, Pakai TTD Terakhir (resolver full signature WOMS), dan visual helper approval existing. Penyimpanan hasil tetap privat dengan validasi PNG/ukuran/coretan; tidak memakai writer signature publik.
- Pengembalian mengunci versi lama. POST Perbaiki Laporan membuka draft document_version + 1 dengan nomor tetap. Nomor/sequence tidak diterbitkan ulang. Semua signed_payload/hash dan signature lama tetap; foto yang dilepas pada revisi hanya soft-delete metadata dan tidak dihapus fisik bila nomor sudah terbit.
- Snapshot versi ditandatangani menjadi sumber PDF; lampiran historis dicari termasuk soft-deleted dengan pemeriksaan hash. Versi lama dilabeli HISTORI/REVISI dan tidak menjadi persetujuan aktif. Versi revisi aktif tidak menampilkan tanda tangan yang dikembalikan. Versi draft baru dimulai tanpa tanda tangan.
- Setelah tiga signature versi sama lengkap, approval committed dahulu, lalu arsip PDF A4 privat dengan checksum dan nama unik dibuat. Kegagalan arsip tidak mengubah approval. Admin dapat mencoba arsip ulang bila path belum tersimpan. Path final yang sudah tersimpan tidak pernah ditimpa; kehilangan file final harus dipulihkan dari backup, bukan regenerasi diam-diam.
- Satu template PDF existing dipertahankan. Dua logo existing SIG/Semen Tonasa, tanpa baris Nama Pelaksana/Tanggal atas, Penanggung Jawab dari pengisi form sesuai permintaan terakhir.

## Admin dan integrasi

- Inspeksi merupakan menu support mandiri tepat sebelum Master Data; Admin non-Super Admin perlu grant inspeksi melalui Access Control existing. Tidak ada auto-grant.
- Empat tab: Baru Masuk, Proses Approval, Selesai, Riwayat. Query badge/tab Baru Masuk sama: signed_at terisi dan belum dibaca oleh admin aktif pada versi tersebut. Draft revisi tidak dihitung; setelah TTD ulang menjadi kiriman baru. Count tab total sebelum filter, dijelaskan pada UI.
- Detail menandai read receipt per Admin/per versi, tanpa perubahan approval. Membuka modal Remarks/PDF tidak menandai detail telah dibaca.
- Monitoring dipaginasi; Remarks hanya satu tombol kuning, jumlah item remark non-whitespace termasuk A. Modal read-only memuat foto privat dan semua nilai A/B/C.
- Admin dapat melihat tiga tahap, histori keputusan/alasan revisi, signature per versi, PDF historis, audit paginated, status email, recovery inisialisasi/arsip, dan resend. Admin tidak bisa menyetujui atas nama signer.
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
2. Pastikan role/email kedua approver, relasi Machine Workshop / Workshop dan TPM Officer tidak ambigu. Uji konfigurasi kosong/ganda/salah role: TTD/nomor tetap, Admin menerima pesan dan dapat retry.
3. Grant menu inspeksi ke subrole Admin yang sesuai. Uji guest, inspector lain, approver lain, Admin tanpa grant, Super Admin; cek detail, PDF, foto, remarks, sign/return/revise/resend/recover.
4. Uji alur tiga signature: hanya Manager menerima email pertama; Leader terkunci; setelah Manager, hanya Leader aktif. Periksa isi email tidak mengandung password, token tidak tercatat di log, dan Inspektor tidak mendapat email.
5. Uji double submit, dua tab, token lama/kedaluwarsa, return tanpa alasan, perubahan payload versi/role/owner, dan resend bersamaan. Nomor tetap saat retry/revisi dan urutan approval tidak terlewati.
6. Uji gagal mail, konfigurasi transport, proses sending tidak terkonfirmasi, cooldown resend, pembaruan token. Uji gagal render/storage PDF: approval tetap sah, pesan arsip terlihat, retry Admin tanpa menimpa final.
7. Uji revisi oleh kedua pejabat, tanggal/checklist/remark/foto berubah, tanda tangan baru tiga tahap, PDF historis/aktif, foto lama tetap tersedia, serta audit perubahan. Kondisi C boleh disetujui tanpa menghapus temuan/menyatakan alat layak.
8. Uji dua Admin membaca bergantian: badge per-admin/per-versi, count Baru Masuk konsisten; revisi yang disubmit lagi kembali menjadi kiriman baru. Modal Remarks dan PDF tidak menulis read state.
9. Review PDF semua 10 form dan remark panjang/foto banyak; layout tiga signature, logo, nomor, status Draft/Revisi/Final, checksum serta hasil arsip tidak berubah setelah data akun/organisasi berubah.
10. Jalankan build aset, pemeriksaan PHP/Blade, targeted regression, dan browser/mobile/canvas secara terpisah. Tidak ada hasil runtime yang diklaim di task ini.

## Batas pemulihan

Tidak ada reassignment approver, rollback signature, edit final, hard delete laporan, atau resend oleh Inspektor. Perubahan organisasi hanya memengaruhi chain baru; chain berjalan mempertahankan signer snapshot. Inisialisasi laporan ready lama harus lewat tindakan recovery Admin, bukan side effect GET atau migrasi data otomatis.
