# Panduan Coding Agent WOMS

## 1. Tujuan dan Cakupan

Dokumen ini adalah panduan permanen bagi coding agent yang mengerjakan seluruh aplikasi WOMS. Aturan berlaku untuk source code, database, UI, dokumen, approval, integrasi antarmodul, testing, dan deployment.

Gunakan tiga kategori berikut saat membaca dokumen ini:

- **Fakta aktual**: perilaku atau struktur yang sudah terdapat pada repository.
- **Aturan wajib**: invariant yang harus dipertahankan atau requirement bisnis yang telah ditetapkan.
- **Rekomendasi**: pola implementasi aman; verifikasi kembali terhadap kode terbaru sebelum dipakai.

Repository adalah sumber kebenaran utama. Request user menentukan scope. Test membantu menjelaskan perilaku, tetapi nama test tidak mengalahkan implementasi aktual dan requirement terbaru.

## 2. Ringkasan Aplikasi

WOMS mengelola siklus pekerjaan dari pengajuan hingga arsip dokumen:

`Order → Scope of Work/dokumen pendukung → Initial Work atau pekerjaan normal → HPP → Verifikasi Anggaran → Purchase Order → Job Waiting/progress atau Workshop → Quality Control → BAST/LHPP → LPJ/PPL → Garansi → Tracking/arsip`

Alur bukan selalu linear:

- Order jasa dan workshop memakai status dan dokumen berbeda.
- Pekerjaan normal memasuki Job Waiting melalui PO yang valid.
- Pekerjaan emergency dapat memasuki Job Waiting melalui Initial Work.
- Workshop memiliki `OrderWorkshop`, QC, task bengkel, PIC, dan public display.
- BAST hanya memakai HPP approved dan memiliki cabang Termin 1/Termin 2.
- Tracking User hanya mengekspos informasi dan dokumen yang memang diizinkan.

## 3. Stack Teknologi

**Fakta aktual:**

- PHP `^8.2`, Laravel 12.
- Blade, Livewire, Flux UI, dan Volt.
- Tailwind CSS 4, Vite 6, Alpine.js, Axios, Lucide.
- SweetAlert digunakan pada beberapa flow konfirmasi/status.
- DomPDF untuk template PDF HTML.
- FPDF dan FPDI untuk pemrosesan/penggabungan PDF.
- PHPUnit 11, Mockery, `RefreshDatabase`, Laravel Pint.
- Frontend entry point: `resources/css/app.css` dan `resources/js/app.js`.

**Aturan wajib:**

- Jangan mengganti framework/library karena preferensi agent.
- Jangan menambah atau menaikkan versi dependency tanpa kebutuhan dan persetujuan.
- Ikuti gaya Laravel repository; gunakan type declaration dan return type secara konsisten.
- Jalankan Pint hanya untuk file PHP yang diubah agar tidak memformat file di luar scope.
- Jalankan build frontend bila class Tailwind, JavaScript, CSS, atau asset berubah.

## 4. Panel, Role, dan Hak Akses

### Role aktual

- `User::ROLE_ADMIN` (`admin`)
- `User::ROLE_USER` (`user`)
- `User::ROLE_PKM` (`pkm`)
- `User::ROLE_APPROVER` (`approver`)
- Subrole admin: `ADMIN_ROLE_SUPER_ADMIN` dan `ADMIN_ROLE_ADMIN`.

### Middleware aktual

- `auth`: user harus login; guest diarahkan ke `login`.
- `role`: `EnsureUserHasRole`, memeriksa role persis yang diberikan.
- `admin_role`: `EnsureAdminHasSubrole`, memastikan role admin dan subrole.
- `admin_menu`: `EnsureAdminMenuAccess`, menggunakan `AdminMenuRegistry`.
- `pkm_panel`: `EnsurePkmPanelAccess`, mengizinkan PKM atau Super Admin.

### Aturan akses

- Panel Admin dijaga backend dengan `role:admin`, lalu `admin_menu`/`admin_role` sesuai route.
- `AdminMenuRegistry` adalah sumber definisi menu, route, badge, visibilitas, dan akses admin.
- Dashboard Admin selalu tersedia untuk admin; Super Admin mendapat bypass menu sesuai model.
- Panel PKM dapat dibuka oleh akun PKM dan Super Admin tanpa mengubah role akun.
- Halaman User dipakai role yang disebut eksplisit pada route; jangan perluas secara implisit.
- Route approval berada dalam `auth`, lalu controller memverifikasi user login cocok dengan signer.
- Menyembunyikan menu bukan pengganti authorization backend.
- Role, subrole, signer, flow, atau permission tidak boleh berasal dari input request.
- Jangan melemahkan middleware hanya agar halaman atau test dapat dibuka.

## 5. Peta Repository

| Modul | Controller utama | Model utama | Service/Support | View | Test |
|---|---|---|---|---|---|
| Authentication/Profile | Auth controllers, `ProfileController` | `User` | Laravel Auth/notification | `livewire/auth`, `admin/profile`, `pkm/profile` | `Auth/*`, `DashboardProfileTest`, `Settings/*` |
| Dashboard | Admin/Pkm `DashboardController` | `Order`, `Hpp`, `LhppBast` | badge/notification center | `dashboards/*`, `pkm/dashboard` | `RoleDashboardAccessTest`, `PkmDashboardInitialWorkTest` |
| Access Control/Admin Menu | `AccessControlController` | `AdminRoleMenuAccess`, `AdminMenuAccess` | `AdminMenuRegistry` | `admin/access-control` | `Admin/AccessControlTest` |
| User Management | Admin/Pkm `UserPanelController` | `User` | snapshot checks pada controller/model | `admin/user-panel`, `pkm/user-panel` | `Admin/UserPanelTest`, `Pkm/UserPanelTest` |
| Struktur Organisasi | `StructureOrganizationController` | `Department`, `UnitWork`, `UnitWorkSection` | approver resolver | `admin/structure` | `StructureOrganizationVendorTest` |
| Order Jasa | Admin `OrderController` | `Order` | `OrderDocumentOverviewService` | `admin/orders/*` | `CreateHppTest`, order filter tests |
| Order Workshop | `OrderWorkshopController` | `Order`, `OrderWorkshop` | `WorkshopOrderTaskSyncer` | `admin/orders/workshop` | `OrderWorkshopIndexUiTest` |
| Scope of Work | `OrderScopeOfWorkController` | `OrderScopeOfWork` | `ScopeOfWorkPdfPresenter` | SOW modal/PDF | `ScopeOfWorkTest` |
| Order Documents | `OrderDocumentController` | `OrderDocument` | `OrderDocumentService` | order detail/modals | `ScopeOfWorkTest`, merged-document tests |
| Initial Work | `InitialWorkController`, approval controller | `InitialWork`, `InitialWorkSignature` | `InitialWorkSignatureService` | Initial Work PDF/approval | approval authorization/email tests |
| Job Waiting | PKM `JobWaitingController` | `Order`, `PurchaseOrder`, `InitialWork` | `PkmSidebarBadgeCounter` | `pkm/jobwaiting` | `JobWaitingProgressValidationTest` |
| Quality Control | workshop QC/approval controllers | `QualityControlReport`, files/signatures | `QualityControlSignatureService` | QC forms/PDF/approval | approval and immutability tests |
| HPP | Admin `HppController`, PKM `HppDraftController` | `Hpp`, `HppSignature` | flow/resolver/builder/number generator | admin/pkm HPP, HPP PDF | HPP feature/unit tests |
| Verifikasi Anggaran | `BudgetVerificationController` | `BudgetVerification`, `Hpp` | admin badge counter | `admin/budget-verification` | budget verification tests |
| Outline Agreement | `OutlineAgreementController` | OA/history/target | `OutlineAgreementService` | `admin/outline-agreements` | HPP/Initial Work integration tests |
| Purchase Order | `PurchaseOrderController` | `PurchaseOrder` | badge/query logic | `admin/purchase-order` | `PurchaseOrderEmergencyTransitionTest` |
| BAST/LHPP | Admin/Pkm `LhppController` | `LhppBast`, image/signature | BAST flow/resolver/builder/deletion | admin/pkm LHPP, PDF | `Bast*` tests |
| LPJ/PPL | `LpjPplController` | `LpjPpl`, `LhppBast` | admin badge counter | `admin/lpj` | `LpjPplDocumentUiTest` |
| Garansi | `GaransiController` | `Garansi`, `LhppBast` | eligibility query | `admin/garansi` | BAST form/sidebar tests |
| Kontrak Fabrikasi/Konstruksi | contract controller | `FabricationConstructionContract` | request validation | contract views | test sesuai repository terbaru |
| Vendor Structure | `VendorStructureController` | vendor type/section | `BastApproverResolver` | modal/layout PKM | `VendorStructureTest`, `VendorManagerTest` |
| Display Pekerjaan Bengkel | `BengkelTaskController` | task/display setting | `WorkshopOrderTaskSyncer` | admin/public display | `BengkelDisplayManagementTest` |
| Bengkel PIC | `BengkelPicController` | `BengkelPic` | storage via controller | `admin/bengkel-pics` | display management tests |
| Upload Informasi | `InformationUploadController` | `AdminInformationFile` | storage/preview controller | information upload | access/route tests terbaru |
| Notification | tiga notification controllers | tiga read model | Admin/Pkm/User center | dropdown pada layout | notification redirect/access tests |
| Approval Inbox | `ApprovalDocumentController` | signature models | `ApprovalDocumentInbox` | `approval-documents/index` | authorization/access tests |
| Signature/Rollback/Reassignment | approval + admin controllers | signature/rollback models | notification, rollback, reassignment | approval/modal | approval feature tests |
| PDF/Dokumen Final | berbagai controller | dokumen/signature models | presenters, `PdfMergeService` | seluruh `*pdf*` | PDF layout/merge tests |
| Order Tracking | `User\OrderTrackingController` | `Order` dan relasi dokumen | `ApprovalWhatsappLink` | `user/orders` | `RoleDashboardAccessTest` |
| Public Display | public routes + Livewire dashboard | `BengkelTask`, setting, PIC | task syncer | `display/bengkel` | `DashboardPekerjaanTest` |

## 6. Alur Dokumen Utama

### Relasi inti

- `Department hasMany UnitWork`; Department menunjuk General Manager.
- `UnitWork belongsTo Department`, menunjuk Senior Manager, dan memiliki section.
- `UnitWorkSection` menunjuk Manager.
- `Order` memiliki documents, satu Scope of Work, satu Initial Work, satu Order Workshop, HPP history, BAST, QC, PO, budget verification, dan garansi.
- `Order::latestHpp()` untuk status/tracking umum; `latestApprovedHpp()` untuk proses yang mensyaratkan HPP approved.
- `Hpp` terhubung ke Order, OA, Budget Verification, PO, creator, dan signature chain.
- `LhppBast` terhubung ke Order, HPP, PO, vendor section, parent/child termin, LPJ/PPL, garansi, image, dan signature.
- Signature HPP, BAST, Initial Work, dan QC menyimpan signer serta snapshot audit.

### Gate utama

- Order HPP eligible bila status jasa sesuai, Scope of Work tersedia, dan belum punya HPP.
- HPP approved menjadi gate BAST; jangan memakai `latestHpp` untuk eligibility BAST.
- Job Waiting normal membutuhkan PO approved Manager dan nomor PO; emergency dapat memakai Initial Work.
- Progress pekerjaan dan kelengkapan dokumen adalah konsep berbeda.
- BAST Termin 1 terkait QC, HPP approved, PO/Initial Work, dan garansi.
- LPJ/PPL dan garansi memengaruhi kelengkapan/finalisasi dokumen.

## 7. Protokol Kerja Agent

1. Baca request dan tetapkan scope eksplisit.
2. Periksa working tree; perubahan existing adalah milik user dan tidak boleh ditimpa.
3. Baca kode terbaru sebelum menyusun solusi.
4. Gunakan `rg`/`rg --files` untuk seluruh penggunaan class, route, field, relasi, status, dan role key.
5. Telusuri `route → middleware → request → controller → service/support → model → database → view → PDF → test`.
6. Jangan mengubah satu file tanpa memeriksa konsumennya.
7. Jangan melakukan global replacement tanpa inspeksi per konteks.
8. Buat perubahan sekecil mungkin; jangan refactor modul stabil untuk fitur kecil.
9. Pertahankan controller Admin bila fitur PKM dapat dibuat terpisah secara aman.
10. Backend adalah sumber kebenaran; Blade/JavaScript bukan tempat rule bisnis utama.
11. Gunakan FormRequest untuk input kompleks.
12. Gunakan transaction untuk operasi multi-record.
13. Gunakan `lockForUpdate()` untuk status, approval, nomor dokumen, dan operasi konkuren.
14. Validasi ulang state di dalam transaction.
15. Gunakan eager loading dan hindari N+1.
16. Pertahankan audit dan data signed.
17. Jangan menyembunyikan exception atau kegagalan external side effect.
18. Tambahkan regression test untuk bug.
19. Jangan menyatakan berhasil sebelum verifikasi dijalankan.
20. Periksa `git diff --check`, diff scoped, dan `git status --short`.
21. Laporkan hal yang sengaja tidak diubah dan kegagalan test yang tidak terkait.

## 8. Konvensi Arsitektur

- Controller dipisahkan berdasarkan `Admin`, `Pkm`, `User`, dan `Approval`.
- FormRequest menangani authorization dan validasi kompleks; jangan duplikasi sembarang di controller.
- Model menangani relasi, casts, status helper, dan query scope yang relevan.
- Flow matrix berada di support class (`HppApprovalFlow`, `BastApprovalFlow`).
- Resolver menentukan approver/effective flow; builder/service membuat signature chain.
- Number generator tidak boleh diduplikasi di controller.
- Rollback dan reassignment berada dalam service approval.
- Presenter/resolver PDF menangani data layout kompleks.
- Notification center dan badge counter dipisahkan per panel.
- Read-only query tidak boleh mempunyai side effect.
- Gunakan constants/enums; jangan hardcode ID user, pejabat, email, unit, seksi, atau department.
- Status atau role key baru mewajibkan audit semua query, badge, notification, PDF, flow, rollback, dan test.
- Jangan membuat template PDF kedua untuk dokumen sama. File bernama copy/legacy bukan alasan menambah duplikasi.

## 9. Aturan Keamanan dan Integritas Data

- Semua write action wajib memiliki authorization backend.
- Token approval bukan satu-satunya otorisasi; user login harus cocok dengan signer.
- Jangan masukkan token, hash, password, credential, atau path sensitif ke log/laporan.
- Signed signature dan signer snapshot adalah data audit.
- Jangan otomatis mengubah `signature_data`, `signed_at`, signer snapshot, signed document path, atau status signed.
- Jangan menghapus audit rollback.
- Jangan hard delete dokumen yang sudah masuk approval tanpa rule eksplisit.
- Jangan mengubah status pembayaran ketika hanya memperbaiki approval.
- Jangan mengubah Termin 1 saat memproses Termin 2.
- Gunakan transaction untuk perubahan status berantai.
- Gunakan after-commit untuk email/file cleanup bila pola aktual mendukungnya.
- Jangan percaya hidden input untuk total, status, role, flow, approver, threshold, atau warranty.
- Upload harus memeriksa MIME, ekstensi, ukuran, dan path; cegah path traversal.
- Jangan mengembalikan absolute storage path ke client.

## 10. Aturan Database dan Migration

- Jangan membuat migration bila perubahan dapat diturunkan dari relasi/data existing.
- Migration hanya untuk perubahan struktur yang benar-benar diperlukan dan diminta.
- Jangan mengedit migration lama yang telah dipakai production; buat migration baru.
- Sebelum unique index, audit data existing dan data legacy.
- Data migration harus null-safe, idempotent, chunked, memiliki dry run dan laporan.
- Gunakan transaction per record/batch dan opsi ID spesifik bila relevan.
- Jangan menjalankan migration production otomatis.
- Jangan menambah kolom hanya untuk label UI atau state yang dapat diturunkan.
- Jangan membuat tabel draft baru bila record utama aman dipakai.
- Pertahankan foreign key/cascade dengan mempertimbangkan audit signature.

## 11. Aturan File, Storage, dan Dokumen

- Gunakan disk dan helper Storage yang sudah digunakan modul.
- Normalisasi path relatif/absolut; jangan mencampurnya tanpa resolver.
- Gunakan folder per jenis dokumen/record.
- Jangan overwrite dokumen signed tanpa proses eksplisit.
- Cleanup file hanya setelah transaction database berhasil.
- Bersihkan staging bila database/upload gagal; jangan tinggalkan state parsial.
- Pertahankan attachment lama saat update kecuali user mengganti/menghapusnya.
- Preview internal harus melalui route terotorisasi.
- File informasi publik hanya mengikuti kategori dan controller publik aktual.
- Jangan mengubah symlink/permission storage melalui source code.

## 12. Aturan UI dan Layout

- Gunakan layout sesuai panel: `components/layouts/admin`, `pkm`, `user`, dan layout approval.
- Pertahankan sidebar, header, notification dropdown, active state, dan responsive behavior.
- Jangan redesign halaman untuk perubahan kecil.
- Gunakan utility Tailwind, Flux UI, Lucide, Alpine, dan pola modal yang sudah ada.
- Jangan menambah framework UI lain.
- Badge/status harus konsisten dengan query daftar tujuan.
- Tabel harus responsif, ringkas, memiliki empty state, tidak melebar karena label, dan memakai pagination untuk data besar.
- Form menampilkan validation error, mempertahankan old input, membedakan readonly/editable, dan mengonfirmasi aksi destruktif.
- Backend tetap memblokir meskipun tombol disembunyikan.
- Hindari inline CSS; jika perlu, scope class agar tidak bocor.
- Hindari class Tailwind dinamis yang tidak terdeteksi build.
- Modal tidak boleh menjadi satu-satunya sumber data penting.
- Gunakan bahasa Indonesia konsisten dan jangan mengganti istilah bisnis tanpa request.

## 13. Aturan PDF

- Gunakan template PDF existing dan periksa semua controller yang merendernya.
- CSS harus kompatibel DomPDF: utamakan table, fixed width, `colgroup`, `border-collapse`, atau float sederhana.
- Hindari CSS Grid, sticky, JavaScript, dan layout browser kompleks.
- Jangan membuat halaman kosong atau memotong tabel tanda tangan.
- Batasi ukuran gambar; nama panjang harus wrap.
- Pertahankan ukuran/orientasi kertas.
- Jangan regenerasi atau mengubah static final PDF yang sudah signed/uploaded.
- Snapshot final harus konsisten dengan signature.
- Perubahan role key harus diikuti template PDF dan test.
- Untuk BAST: Manager PKM tetap terpisah bila desain aktual demikian; cell utama proporsional; kolom dinamis; Manager sama dapat dicollapse sesuai resolver PDF.
- Jangan menyentuh PDF lain di luar scope.

## 14. Aturan Notification dan Badge

- Notification center dan read model dipisahkan untuk Admin, PKM, dan User.
- Badge sidebar harus memakai eligibility yang sama dengan list tujuan.
- Jangan hitung record yang tidak dapat dibuka user.
- Mark-as-read hanya mengubah read state user terkait, bukan status dokumen.
- Notification approval dikirim setelah commit bila memungkinkan.
- Resend hanya untuk active signature/token yang valid.
- Jangan kirim email/WhatsApp ganda untuk step sama.
- Jangan tampilkan token mentah pada notification list.
- Saat eligibility berubah, periksa notification center, badge, dashboard card, approval inbox, dan query list.

## 15. Aturan Approval Umum

- Approval berurutan; satu step aktif/pending kecuali flow aktual mendefinisikan lain.
- Step berikutnya locked sampai step sebelumnya selesai.
- Approver di-resolve dari struktur/config resmi; snapshot disimpan saat chain dibuat.
- Perubahan organisasi tidak boleh mengubah snapshot dokumen lama.
- DIROPS terakhir bila flow memerlukannya.
- Token memiliki expiry sesuai model/service.
- Sign/reject harus idempotent; double submit tidak membuat signature ganda.
- Reassignment mengikuti rule service dan tetap tercatat.
- Rollback mempertahankan audit, mereset step terdampak, tidak mengubah step sebelumnya, serta membersihkan token/final document yang memang terdampak.
- History paraf dan tanda tangan penuh tidak boleh tercampur.
- BAST, Initial Work, dan QC memakai tanda tangan penuh.
- HPP membedakan paraf/TTD melalui `HppApprovalMarkResolver` dan role key.
- Role HPP `manager_peminta`, `manager_pengendali`, `manager_counter_part` memakai paraf; Manager Workshop dan role lain memakai TTD penuh.
- Jangan menyalin signature ke role lain tanpa rule eksplisit.

## 16. Aturan per Modul

### 16.1 Authentication dan Profile

- Login memakai auth Laravel/Livewire; registrasi publik dinonaktifkan oleh test aktual.
- Reset password memakai notification khusus dan password selalu di-hash.
- Profile Admin dan PKM memiliki route/layout panel masing-masing.
- `dashboardRouteName()` menentukan redirect berdasarkan role; jangan ubah tanpa audit seluruh login/approval redirect.
- Test: `tests/Feature/Auth`, `DashboardProfileTest`, `Settings`.

### 16.2 Access Control dan Admin Menu

- Super Admin mengakses semua menu dan halaman access control.
- Admin biasa mengikuti `AdminRoleMenuAccess`.
- `AdminMenuRegistry` menentukan definisi dan sidebar; middleware route tetap wajib.
- Perubahan menu harus sinkron antara registry, route, layout, dan test.

### 16.3 User Panel

- Admin mengelola role yang diizinkan; PKM User Panel dibatasi akun PKM.
- Jangan menerima role/admin_role palsu dari request.
- User aktif pada approval tidak boleh dihapus tanpa rule; signature lama mempertahankan snapshot.
- Periksa controller kedua panel dan test user panel sebelum mengubah.

### 16.4 Struktur Organisasi

- `Department → generalManager`, `UnitWork → seniorManager`, `UnitWorkSection → manager`.
- Struktur adalah sumber approver HPP, Initial Work, QC, dan flow lain.
- Jangan hardcode approver.
- Perubahan struktur hanya memengaruhi resolusi baru, bukan snapshot lama.

### 16.5 Order Jasa

- `OrderUserNoteStatus`: `approved_jasa`, `approved_workshop`, `approved_workshop_jasa`, `pending`, `reject`.
- Priority memakai constant `Order`; label bisnis tidak sama dengan nama konstanta generik.
- Unit, seksi, kategori, status, dan dokumen menentukan downstream eligibility.
- User tracking tidak boleh menampilkan data internal.
- Validasi berasal dari request Admin Orders.

### 16.6 Order Workshop

- `OrderWorkshop` memiliki status material, anggaran, dan progress workshop.
- Progress aktual: menunggu jadwal, in progress, QC, pending, done.
- Workshop terhubung ke QC, BengkelTask, PIC, dan public display.
- Sinkronisasi task dilakukan melalui `WorkshopOrderTaskSyncer`.

### 16.7 Scope of Work

- Fakta aktual: `Order` memiliki relasi `hasOne` Scope of Work.
- SOW menyimpan item teknis, tanggal, creator, dan dirender ke PDF existing.
- SOW menjadi gate HPP.
- Signature canvas/riwayat harus mengikuti user dan storage signature yang sah.
- Jangan membuat SOW kedua untuk order sama.

### 16.8 Order Documents

- Jenis berasal dari `OrderDocumentType`.
- Upload/preview/download/delete melalui controller dan `OrderDocumentService`.
- MIME/path dan authorization wajib diperiksa.
- Overview memakai `OrderDocumentOverviewService`.
- Jangan membuat dokumen internal publik demi kemudahan preview.

### 16.9 Initial Work

- Initial Work terkait Order, OA/unit/section, creator, dan signature chain.
- Snapshot OA menjaga dokumen lama saat OA berubah.
- Flow dibuat/diaktifkan/regenerate token melalui `InitialWorkSignatureService`.
- Emergency Initial Work dapat menjadi sumber Job Waiting.
- Signed document immutable; perubahan flow harus memeriksa PDF, builder/service, approval page, dan tests.

### 16.10 Job Waiting

- Sumber normal: PO approved Manager dengan nomor PO.
- Sumber emergency: Initial Work untuk priority yang diizinkan.
- Progress tidak boleh turun; tanggal mulai/selesai diturunkan backend.
- Progress 100 menandai pekerjaan selesai pada Dashboard, tetapi card Job Waiting tetap mengikuti kelengkapan dokumen final.
- Jangan ubah pekerjaan selesai tanpa rule eksplisit.
- Badge harus sama dengan query `JobWaitingController`.

### 16.11 Quality Control

- QC memiliki tipe `fabrication`/`refurbish`, status record `draft`/`submitted`, files, payload, dan signatures.
- Approval status diturunkan dari signature chain.
- QC terhubung ke Order/BengkelTask dan menjadi gate BAST sesuai controller aktual.
- Signed QC/file immutable.
- Full signature dipakai; jangan mengambil paraf HPP.

### 16.12 HPP

- Satu order satu HPP; fitur Duplicate HPP dilarang.
- Eligible: status jasa sesuai, Scope of Work ada, belum memiliki HPP.
- Status: `draft`, `in_review`, `approved`, `rejected`.
- PKM dan Admin berbagi record; PKM hanya menyimpan draft, Admin melakukan submit approval.
- Total, bucket, case, dan flow dihitung backend.
- Threshold `HppApprovalFlow::THRESHOLD` adalah Rp250.000.000.
- Flow kategori/area/bucket berasal dari `HppApprovalFlow`.
- Approver, signature chain, dan nomor dokumen memakai resolver/builder/generator.
- BAST hanya menggunakan `latestApprovedHpp`; `latestHpp` tetap untuk tracking umum.
- Paraf/TTD dibedakan melalui role key, bukan kolom baru.
- Manager Workshop menggunakan TTD penuh.

### 16.13 Verifikasi Anggaran

- Terhubung ke HPP dan Order; opsi/status berasal dari model/controller aktual.
- Hanya HPP approved yang masuk proses sesuai counter/query.
- Partial update tidak boleh menghapus field lain.
- Dropdown harus benar-benar tersimpan, bukan hanya berubah di browser.
- Badge harus sama dengan daftar waiting.

### 16.14 Outline Agreement

- OA memiliki unit, status draft/active/expired/closed, periode, nilai, histories, targets, dan latest history.
- Amendment dapat memperpanjang, menambah nilai, kombinasi, atau revisi.
- `OutlineAgreementService` menjaga operasi dan histori.
- HPP/Initial Work menyimpan snapshot agar dokumen lama stabil.
- Jangan mengubah OA lama melalui perubahan dokumen downstream.

### 16.15 Purchase Order

- Menyimpan nomor PO, HPP/Order, target, approval target, approval flags, progress, actual dates, dokumen, dan catatan.
- PO valid untuk Job Waiting ketika Manager approved dan nomor terisi.
- Progress 100 berperan pada eligibility BAST/garansi sesuai query aktual.
- Checkbox DIROPS hanya relevan di atas threshold menurut test aktual.
- Backend harus mengabaikan flag DIROPS tidak sah untuk nilai ≤ Rp250 juta.

### 16.16 BAST/LHPP

- HPP wajib approved; Termin 2 memakai HPP yang sama dengan parent Termin 1.
- Termin 1/Termin 2 terhubung parent-child.
- Dengan garansi > 0: Termin 1 95%, Termin 2 5%.
- Tanpa garansi/0 bulan: Termin 1 100%, Termin 2 0 dan Termin 2 tidak dibuat.
- Threshold BAST berdasarkan nilai termin sesuai controller aktual.
- Termin 1 melalui QC sesuai gate aktual.
- Flow under: Manager PKM, Manager Peminta, Manager Pengendali, SM Pengendali, GM Pengendali.
- Over threshold menambahkan DIROPS terakhir.
- Manager Peminta/Pengendali dengan user sama dikonsolidasikan pada BAST baru.
- Legacy BAST tidak dimigrasikan hanya untuk layout; resolver PDF boleh collapse signer ID sama.
- Manager berbeda tetap dua tahap; Manager PKM tetap terpisah.
- Jangan mencampur payment status dengan approval status.

### 16.17 LPJ/PPL

- `LpjPpl belongsTo LhppBast`; menyimpan data/dokumen per termin.
- Kelengkapan LPJ dan PPL memengaruhi badge Dokumen/Job Waiting.
- Update hanya melalui authorization Admin dan request terkait.
- Delete/rollback harus memeriksa approval, payment, file, dan data termin.

### 16.18 Garansi

- Garansi terhubung ke Order dan dapat terhubung ke BAST.
- Nilai 0 bulan berarti tanpa Termin 2; nilai > 0 membuka kemungkinan Termin 2.
- Timing eligibility mengikuti `GaransiController` dan badge terbaru.
- Bukti/foto mengikuti storage rule.
- Jangan mengubah garansi setelah downstream berjalan tanpa validasi state.

### 16.19 Kontrak Fabrikasi/Konstruksi

- Model menyimpan kategori/kontrak dan audit creator/updater sesuai request.
- Validasi berada di `StoreFabricationConstructionContractRequest`.
- Hubungan dengan pekerjaan/vendor harus ditelusuri sebelum mengubah opsi.
- Jangan hardcode kontrak atau vendor pada fitur downstream.

### 16.20 Vendor Structure

- Vendor tetap aktual adalah `VendorWorkType::FIXED_VENDOR_NAME`; section vendor disimpan terpisah.
- Manager PKM di-resolve dari `VendorWorkTypeSection`.
- Jangan hardcode Manager PKM.
- Perubahan manager berlaku untuk approval baru; snapshot lama tetap.
- Periksa `VendorStructureTest`, `VendorManagerTest`, dan `BastApproverResolverTest`.

### 16.21 Display Pekerjaan Bengkel dan Bengkel PIC

- BengkelTask menyimpan PIC, progress, completed, pending reason, attachment, archive, dan relasi order.
- Archive dapat membuat/mengaitkan order workshop sesuai service/controller.
- Task archived tidak muncul pada public display.
- Public display memakai Livewire dan setting display.
- Avatar/attachment mengikuti storage dan object position.
- Bulk action tetap membutuhkan authorization dan validasi target.

### 16.22 Upload Informasi

- Kategori aktual meliputi cara kerja, flowchart aplikasi, dan kontrak PKM.
- Controller mengelola upload, preview, download, delete, dan route publik yang eksplisit.
- Visibility publik harus mengikuti route/controller, bukan asumsi dari lokasi file.
- Validasi MIME/path dan cleanup wajib.

### 16.23 Dashboard

- Dashboard hanya membaca/agregasi; jangan tambahkan side effect.
- Count/chart harus konsisten dengan list utama.
- Filter tahun/bulan harus mempertahankan query dan format biaya.
- Dashboard PKM membedakan pekerjaan selesai (progress 100) dari dokumen final.
- Perubahan status harus diuji pada card, breakdown, chart, target kalender, dan badge.

### 16.24 Notification

- Read state terpisah: `AdminNotificationRead`, `PkmNotificationRead`, `UserNotificationRead`.
- Mark read/read all hanya untuk user aktif.
- Link tujuan harus aman; PKM memvalidasi redirect internal.
- Notification tidak mengubah status dokumen.

### 16.25 Order Tracking

- User melihat order miliknya/sesuai authorization dan timeline yang diizinkan.
- Dokumen download/preview mengikuti route terotorisasi.
- Jangan ekspos token approval, storage path, catatan internal, atau approval milik user lain.
- Periksa modal flow dan timeline ketika mengubah status.

### 16.26 Approval Document Inbox

- `ApprovalDocumentInbox` menyusun approval pending untuk approver login.
- Jangan menampilkan approval user lain.
- Link approval tetap melewati auth dan signer authorization.
- Badge inbox, query halaman, dan status aktif harus konsisten.

### 16.27 Public Routes dan Display

- Fakta publik saat ini mencakup home, public information preview yang didefinisikan, avatar PIC, dan display pekerjaan bengkel.
- Health route `/up` berasal dari bootstrap.
- Jangan menjadikan dokumen order/HPP/BAST/QC internal sebagai publik.
- Audit route baru terhadap middleware dan kebocoran identifier.

## 17. Aturan Testing

- Jalankan targeted test modul lebih dahulu, lalu full suite bila environment memungkinkan.
- Jangan menghapus/melemahkan test agar hijau.
- Perbarui fixture bila rule resmi berubah; jangan memalsukan state yang mustahil.
- Gunakan `RefreshDatabase` sesuai pola repository.
- Authorization minimal mencakup guest, user, approver, PKM, admin, dan Super Admin bila relevan.
- Test state transition, request manipulation, double submit, idempotency, upload/storage, dan data audit.
- Perubahan PDF memerlukan render/layout regression test.
- Perubahan badge memerlukan sinkronisasi query list.
- Test legacy data bila requirement menyebut kompatibilitas.
- Approval finansial diuji under/over threshold, signer sama/berbeda, garansi/tanpa garansi, Termin 1/2.
- Sesuaikan nama test dengan repository terbaru; jangan menciptakan nama sebagai kewajiban tanpa memastikan file ada.

Perintah umum:

```bash
php artisan optimize:clear
php artisan route:list
php artisan test --filter=NamaTest
php artisan test
vendor/bin/pint path/to/changed.php
php -l path/to/changed.php
npm run build
```

Catatan: `optimize:clear` dapat membutuhkan backend cache/database sesuai `.env`; laporkan kendala environment dengan jujur. `npm run build` hanya wajib jika frontend berubah.

## 18. Deployment dan Validasi

Urutan aman umum:

1. Review `git status`, `git diff --check`, dan diff scoped.
2. Backup database bila ada migration/data command.
3. Pull source.
4. Jalankan `composer install` hanya bila dependency PHP berubah.
5. Jalankan `npm ci && npm run build` bila dependency/asset frontend berubah.
6. Jalankan `php artisan migrate --force` hanya untuk migration yang telah direview.
7. Jalankan `php artisan optimize:clear`.
8. Buat config/route/view cache sesuai strategi environment.
9. Restart queue worker hanya bila code queue/job berubah.
10. Restart PHP-FPM/container/service sesuai environment, tanpa hardcode server.
11. Smoke test login, dashboard, route modul, PDF, approval link, dan upload yang relevan.

Jangan memasukkan credential, domain internal, IP, atau perintah server spesifik ke dokumentasi/repository.

## 19. Definition of Done

Task selesai bila:

- Scope user terpenuhi dan root cause dijelaskan.
- Tidak ada perubahan di luar scope tanpa alasan.
- Authorization backend dan state transition aman.
- Audit/signed data tetap aman.
- Perhitungan backend konsisten.
- Konsumen downstream telah diperiksa.
- UI mengikuti layout existing; PDF tetap rapi bila terdampak.
- Regression/targeted test lulus.
- Full suite dijalankan bila memungkinkan; failure existing dipisahkan jelas.
- Pint, syntax check, dan build relevan lulus.
- Tidak ada migration/dependency yang tidak diminta.
- Diff telah diperiksa.
- Laporan menyebut perintah yang benar-benar dijalankan.

## 20. Larangan Utama

- Jangan hardcode user approver.
- Jangan melemahkan middleware.
- Jangan percaya status, total, flow, threshold, warranty, atau approver dari frontend.
- Jangan mengubah signed data otomatis atau menghapus audit.
- Jangan membuat migration tanpa kebutuhan atau mengubah migration production lama.
- Jangan mengembalikan Duplicate HPP.
- Jangan memakai HPP non-approved untuk BAST.
- Jangan mengubah threshold/persentase tanpa instruksi.
- Jangan membuat Termin 2 tanpa garansi.
- Jangan melewati step approver wajib atau memindahkan DIROPS dari posisi akhirnya.
- Jangan mencampur paraf HPP dengan TTD penuh.
- Jangan menghapus signature legacy hanya untuk layout.
- Jangan mengubah template PDF lain di luar scope.
- Jangan refactor besar modul stabil untuk perubahan kecil.
- Jangan membuat route publik untuk dokumen internal.
- Jangan mengirim token/credential ke log.
- Jangan global search-and-replace tanpa inspeksi.
- Jangan mengubah enum/status/role key tanpa memeriksa seluruh konsumen.
- Jangan menjalankan aksi destruktif pada working tree/data tanpa izin.
- Jangan menyatakan test lulus bila tidak dijalankan.

## 21. Format Laporan Akhir

Setiap coding agent wajib melaporkan:

1. Ringkasan permintaan.
2. Hasil analisis.
3. Root cause.
4. Daftar file yang dibuat/diubah.
5. Perubahan per file.
6. Dampak ke modul lain.
7. Rule bisnis yang dipertahankan.
8. Database/migration yang dibuat atau konfirmasi tidak ada.
9. Test yang dijalankan.
10. Hasil test, termasuk failure yang tidak terkait.
11. Build, lint, syntax, dan Blade check.
12. Risiko/kompatibilitas data lama.
13. Langkah deployment yang benar-benar diperlukan.
14. Hal yang sengaja tidak diubah.

Laporan “Sudah selesai” atau “Sudah diperbaiki” tanpa bukti tidak memadai.
