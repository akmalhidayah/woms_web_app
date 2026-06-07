@php
    $safeName = trim((string) ($userName ?? 'Pengguna'));
    $logoStSource = $logoStUrl ?? '';
    $logoBmsSource = $logoBmsUrl ?? '';

    if (isset($message) && ! empty($logoStPath) && file_exists($logoStPath)) {
        $logoStSource = $message->embedData(file_get_contents($logoStPath), 'semen-tonasa-logo.png', 'image/png');
    }

    if (isset($message) && ! empty($logoBmsPath) && file_exists($logoBmsPath)) {
        $logoBmsSource = $message->embedData(file_get_contents($logoBmsPath), 'bms-logo.png', 'image/png');
    }
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permintaan Approval WOMS</title>
</head>
<body style="margin:0;padding:0;background:#edf3f8;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Permintaan approval dokumen {{ $documentType }} nomor {{ $documentNumber }} melalui sistem WOMS.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#edf3f8;">
        <tr>
            <td align="center" style="padding:34px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#ffffff;border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="background:#0f172a;padding:26px 32px;">
                            @if (! empty($logoStSource))
                                <img src="{{ $logoStSource }}" width="52" alt="Semen Tonasa" style="display:inline-block;background:#fff;border-radius:12px;padding:7px;vertical-align:middle;">
                            @endif

                            @if (! empty($logoBmsSource))
                                <img src="{{ $logoBmsSource }}" width="54" alt="Bengkel Mesin" style="display:inline-block;background:#fff;border-radius:12px;padding:7px;margin-left:8px;vertical-align:middle;">
                            @endif

                            <span style="display:inline-block;margin-left:16px;color:#fff;font-size:21px;font-weight:800;vertical-align:middle;">
                                Workshop Order Management System
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 34px 12px;">
                            <h1 style="margin:0;font-size:26px;line-height:34px;">
                                Halo {{ $safeName }}
                            </h1>
                            <p style="margin:16px 0 0;color:#475569;font-size:15px;line-height:25px;">
                                Anda ditetapkan sebagai <strong>{{ $roleLabel }}</strong> untuk melakukan approval dokumen berikut.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;">
                                <tr>
                                    <td style="padding:16px 18px;color:#64748b;font-size:13px;">Dokumen</td>
                                    <td style="padding:16px 18px;text-align:right;font-weight:800;">{{ $documentType }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 16px;color:#64748b;font-size:13px;">Nomor</td>
                                    <td style="padding:0 18px 16px;text-align:right;font-weight:800;">{{ $documentNumber }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 16px;color:#64748b;font-size:13px;">Role Approval</td>
                                    <td style="padding:0 18px 16px;text-align:right;font-weight:800;">{{ $roleLabel }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 34px 26px;">
                            <a href="{{ $approvalUrl }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;border-radius:13px;padding:14px 28px;font-size:14px;font-weight:800;">
                                Buka Halaman Approval
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 34px 30px;color:#64748b;font-size:12px;line-height:20px;">
                            @if ($expiresAt)
                                <p style="margin:0 0 10px;">
                                    Link berlaku sampai <strong>{{ $expiresAt }}</strong>.
                                </p>
                            @endif

                            <p style="margin:0 0 10px;">
                                Link hanya dapat digunakan oleh akun approval yang ditetapkan.
                            </p>

                            <p style="margin:0 0 8px;">
                                Jika tombol tidak dapat dibuka, salin dan tempel link berikut ke browser:
                            </p>

                            <div style="word-break:break-all;color:#2563eb;">
                                {{ $approvalUrl }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 32px;color:#64748b;font-size:12px;text-align:center;">
                            Email ini dikirim otomatis oleh sistem WOMS. Mohon tidak membalas email ini.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
