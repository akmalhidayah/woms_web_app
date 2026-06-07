Halo {{ $userName ?? 'Pengguna' }}

Anda ditetapkan sebagai {{ $roleLabel }} untuk melakukan approval dokumen berikut:

Dokumen        : {{ $documentType }}
Nomor Dokumen : {{ $documentNumber }}
Role Approval : {{ $roleLabel }}

Silakan buka halaman approval melalui link berikut:
{{ $approvalUrl }}

@if ($expiresAt)
Link berlaku sampai {{ $expiresAt }}.
@endif

Link hanya dapat digunakan oleh akun approval yang ditetapkan.

Email ini dikirim otomatis oleh sistem WOMS. Mohon tidak membalas email ini.
