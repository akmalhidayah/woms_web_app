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
                                    <td style="background:#0f172a;padding:28px 32px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td style="width:78px;height:66px;border-radius:18px;background:#ffffff;text-align:center;vertical-align:middle;box-shadow:0 12px 28px rgba(2,6,23,.22);">
                                                                <img src="{{ $logoStSource }}" width="56" alt="Semen Tonasa" style="width:56px;max-width:56px;height:auto;display:inline-block;border:0;vertical-align:middle;">
                                                            </td>
                                                            <td style="width:12px;"></td>
                                                            <td style="width:78px;height:66px;border-radius:18px;background:#ffffff;text-align:center;vertical-align:middle;box-shadow:0 12px 28px rgba(2,6,23,.22);">
                                                                <img src="{{ $logoBmsSource }}" width="58" alt="Bengkel Mesin" style="width:58px;max-width:58px;height:auto;display:inline-block;border:0;vertical-align:middle;">
                                                            </td>
                                                            <td style="padding-left:18px;color:#ffffff;">
                                                                <div style="font-size:12px;line-height:18px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#93c5fd;">WOMS</div>
                                                                <div style="font-size:24px;line-height:31px;font-weight:800;margin-top:2px;">Workshop Order Management System</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:36px 34px 12px;">
                                        <h1 style="margin:0;color:#0f172a;font-size:28px;line-height:36px;font-weight:800;">Halo {{ $safeName }}</h1>
                                        <p style="margin:16px 0 0;color:#475569;font-size:16px;line-height:26px;">
                                            Kami menerima permintaan reset password untuk akun WOMS Anda. Gunakan tombol di bawah untuk membuat password baru.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:24px 34px 26px;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:13px;padding:15px 30px;font-size:15px;line-height:20px;font-weight:800;box-shadow:0 12px 26px rgba(37,99,235,.3);">Reset Password</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 34px 28px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;">
                                            <tr>
                                                <td style="padding:18px 20px;">
                                                    <div style="color:#0f172a;font-size:14px;font-weight:800;margin-bottom:6px;">Link berlaku {{ $expiresIn }} menit</div>
                                                    <div style="color:#64748b;font-size:13px;line-height:22px;">Jika Anda tidak meminta reset password, abaikan email ini. Password lama tetap aktif sampai Anda membuat password baru.</div>
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
