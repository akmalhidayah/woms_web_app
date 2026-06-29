Halo Bpk/Ibu {{ $userName ?? 'Pengguna' }},

{{ $roleLabel }} PT. Semen Tonasa, Silahkan Lakukan review dan approval dokumen berikut melalui link di bawah ini.

Dokumen        : {{ $documentType }}
Nomor Dokumen : {{ $documentNumber }}
Role Approval : {{ $roleLabel }}

Akses Login Approval
Email Resmi SIG  : {{ ($loginEmail ?? '') ?: '-' }}
Password default : {{ $defaultPassword ?? 'bengkelmesin123' }}

@if (! empty($guideUrl))
Sebelum melakukan tanda tangan, mohon membaca buku panduan role approval berikut:
{{ $guideTitle ?? 'Buku Panduan Role Approval' }}
{{ $guideUrl }}

@endif

Mohon lakukan review dan approval melalui link berikut:
{{ $approvalUrl }}

@if ($expiresAt)
Link approval ini berlaku sampai {{ $expiresAt }}.
@endif

Link hanya dapat digunakan oleh akun yang ditetapkan sebagai approver. Jangan meneruskan link ini kepada pihak lain.

Jika approval sudah Anda selesaikan, email ini dapat diabaikan.

Email ini dikirim otomatis oleh sistem WOMS. Mohon tidak membalas email ini.
