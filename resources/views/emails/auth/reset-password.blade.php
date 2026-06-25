@php
    $safeName = trim((string) ($userName ?? 'Pengguna'));
    $logoStSource = $logoStUrl ?? '';
    $logoBmsSource = $logoBmsUrl ?? '';

    if (isset($message) && ! empty($logoStPath) && file_exists($logoStPath)) {
        $logoStSource = $message->embedData(
            file_get_contents($logoStPath),
            'semen-tonasa-logo.png',
            'image/png'
        );
    }

    if (isset($message) && ! empty($logoBmsPath) && file_exists($logoBmsPath)) {
        $logoBmsSource = $message->embedData(
            file_get_contents($logoBmsPath),
            'bms-logo.png',
            'image/png'
        );
    }
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password Akun WOMS</title>
</head>
<body style="margin:0;padding:0;background:#edf3f8;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#edf3f8;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:34px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;max-width:660px;border-collapse:separate;border-spacing:0;">
                    <tr>
                        <td style="border-radius:24px;overflow:hidden;background:#ffffff;box-shadow:0 22px 54px rgba(15,23,42,.16);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center" style="background:#eef3f8;padding:30px 32px 28px;text-align:center;border-bottom:1px solid #dbe4ee;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;">
                                            <tr>
                                                <td align="center" style="text-align:center;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto;margin-left:auto;margin-right:auto;">
                                                        <tr>
                                                            @if (! empty($logoStSource))
                                                                <td align="center" style="padding:0 8px;vertical-align:middle;">
                                                                    <img src="{{ $logoStSource }}" width="64" alt="Semen Tonasa" style="display:block;border:0;outline:none;text-decoration:none;">
                                                                </td>
                                                            @endif

                                                            @if (! empty($logoBmsSource))
                                                                <td align="center" style="padding:0 8px;vertical-align:middle;">
                                                                    <img src="{{ $logoBmsSource }}" width="78" alt="Bengkel Mesin" style="display:block;border:0;outline:none;text-decoration:none;">
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top:14px;color:#0f172a;font-size:24px;line-height:30px;font-weight:800;text-align:center;">
                                                    Workshop Order Management System
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:36px 34px 12px;">
                                        <h1 style="margin:0;color:#0f172a;font-size:28px;line-height:36px;font-weight:800;">Halo Bpk/Ibu {{ $safeName }},</h1>
                                        <p style="margin:16px 0 0;color:#475569;font-size:16px;line-height:26px;">
                                            Kami menerima permintaan reset password untuk akun WOMS Anda. Gunakan tombol di bawah ini untuk membuat password baru.
                                        </p>
                                        <p style="margin:10px 0 0;color:#475569;font-size:16px;line-height:26px;">
                                            Jika permintaan ini bukan dari Anda, abaikan email ini. Password lama tetap aktif sampai Anda membuat password baru.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:24px 34px 26px;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:13px;padding:15px 30px;font-size:15px;line-height:20px;font-weight:800;box-shadow:0 12px 26px rgba(37,99,235,.3);">Buat Password Baru</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 34px 28px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;">
                                            <tr>
                                                <td style="padding:18px 20px;">
                                                    <div style="color:#0f172a;font-size:14px;font-weight:800;margin-bottom:6px;">Link berlaku {{ $expiresIn }} menit</div>
                                                    <div style="color:#64748b;font-size:13px;line-height:22px;">Demi keamanan akun, jangan meneruskan link reset password ini kepada pihak lain.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 34px 36px;">
                                        <div style="color:#64748b;font-size:12px;line-height:20px;margin-bottom:8px;">Jika tombol tidak bisa diklik, salin link berikut ke browser:</div>
                                        <div style="word-break:break-all;color:#2563eb;font-size:12px;line-height:19px;">{{ $resetUrl }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 32px;color:#64748b;font-size:12px;line-height:19px;text-align:center;">
                                        Email ini dikirim otomatis oleh sistem WOMS. Mohon tidak membalas email ini.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
