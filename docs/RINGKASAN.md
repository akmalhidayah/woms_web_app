# Ringkasan WOMS

Baca ini sebelum coding. Detail lengkap: `docs/PANDUAN_LENGKAP_WOMS.md` (baca bagian relevan saja).

## Alur utama
Order → Scope of Work (SOW) → Initial Work → HPP → Verifikasi Anggaran → Purchase Order (PO) → Job Waiting → Quality Control (QC) → BAST/LHPP → LPJ/PPL → Garansi → Tracking/arsip

Alur tidak selalu linear:
- Order jasa dan workshop berbeda status/dokumen; workshop punya `OrderWorkshop`, QC, task bengkel, PIC, public display.
- Job Waiting normal butuh PO approved Manager + nomor PO; emergency bisa via Initial Work.
- BAST hanya memakai HPP approved, dengan cabang Termin 1/Termin 2.
- Tracking User hanya mengekspos data/dokumen yang diizinkan.

## Stack
PHP ^8.2, Laravel 12, Blade, Livewire, Flux UI, Volt, Tailwind 4, Vite 6, Alpine.js, DomPDF, FPDF/FPDI, PHPUnit 11, Pint.

## Role dan akses
Role: `admin` (subrole super admin/admin), `user`, `pkm`, `approver`.
Middleware: `auth`, `role`, `admin_role`, `admin_menu` (via `AdminMenuRegistry`), `pkm_panel`.
Otorisasi selalu di backend; menyembunyikan menu bukan pengganti.

## Modul inti
- **HPP**: satu order satu HPP; status draft/in_review/approved/rejected. PKM simpan draft, Admin submit approval. Total, bucket, flow dihitung backend. Threshold Rp250.000.000 (`HppApprovalFlow::THRESHOLD`). Paraf untuk manager_peminta/pengendali/counter_part; lainnya TTD penuh.
- **BAST/LHPP**: wajib HPP approved (`latestApprovedHpp()`).
  - Dengan garansi > 0: Termin 1 = 95%, Termin 2 = 5%.
  - Tanpa garansi / 0 bulan: Termin 1 = 100%, Termin 2 tidak dibuat.
  - Termin 2 memakai HPP yang sama dengan parent Termin 1.
  - Over threshold: DIROPS step terakhir.
- **Approval**: berurutan, satu step aktif, step berikutnya locked; approver di-resolve dari struktur resmi dan disimpan sebagai snapshot saat chain dibuat (perubahan organisasi tidak mengubah dokumen lama). Token ber-expiry; sign/reject idempotent; rollback menjaga audit.
- **Dokumen signed** immutable; PDF final tidak diregenerasi.

## Daftar dokumen integrasi (`docs/integrations/`)
- `README.md` — index file integrasi.
- `woms-organization-api.md` — WOMS Organization API v1: API read-only (Sanctum, GET) untuk sinkronisasi Departemen/Unit Kerja/Seksi dan pejabat ke aplikasi Overhaul.
