@php
    $dimensionRows = collect($payload['dimension_checks'] ?? []);
    $materialRows = collect($payload['materials'] ?? []);
    $weldingRows = collect($payload['welding'] ?? []);
    $beforeFiles = collect($filesByCategory->get('fabrication_before', collect()));
    $afterFiles = collect($filesByCategory->get('fabrication_after', collect()));
    $signature = collect($payload['signature'] ?? []);
    $signatureData = \App\Support\SignatureImageStorage::imageSource((string) $signature->get('signature_data', '')) ?: '';
    $signatureName = (string) $signature->get('signer_name', '');
    $signatureDate = (string) $signature->get('signed_at', '');
    $qcSignatures = ($report->relationLoaded('signatures') ? $report->signatures : $report->signatures()->get())
        ->keyBy('role_key');
    $approvalSignatureFor = fn (string $roleKey) => $qcSignatures->get($roleKey);
    $approvalSignatureData = fn ($approvalSignature): string => $approvalSignature?->isSigned()
        ? (string) (\App\Support\SignatureImageStorage::imageSource((string) $approvalSignature->signature_data) ?: '')
        : '';
    $approvalSignatureName = fn ($approvalSignature): string => $approvalSignature?->isSigned() ? (string) $approvalSignature->signer_name : '';
    $approvalSignatureRole = fn ($approvalSignature): string => $approvalSignature?->isSigned() ? (string) ($approvalSignature->signer_position ?: $approvalSignature->role_label) : '';
    $approvalSignatureDate = static function ($approvalSignature): string {
        if (! $approvalSignature?->signed_at) {
            return '';
        }

        return \Carbon\Carbon::parse($approvalSignature->signed_at)->format('d/m/Y');
    };
    $workshopManagerSignature = $approvalSignatureFor(\App\Models\QualityControlSignature::ROLE_WORKSHOP_MANAGER);
    $userManagerSignature = $approvalSignatureFor(\App\Models\QualityControlSignature::ROLE_USER_MANAGER);
    $imagePath = function ($file): ?string {
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($file->file_path);
        return is_file($path) ? str_replace('\\', '/', $path) : null;
    };
    $sigLogo = str_replace('\\', '/', public_path('assets/branding/logos/logo-sig.png'));
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18px 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 5px 6px; vertical-align: top; }
        th { background: #d9e2f3; font-weight: bold; text-align: center; }
        .header td { border: 1.5px solid #111; }
        .logo { width: 72px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; letter-spacing: .5px; }
        .section-title { margin-top: 10px; background: #1f4e79; color: #fff; border: 1px solid #111; padding: 6px; font-weight: bold; text-transform: uppercase; }
        .meta td { height: 22px; }
        .check { font-family: DejaVu Sans, sans-serif; font-size: 12px; text-align: center; }
        .notes { min-height: 46px; border: 1px solid #111; padding: 7px; }
        .signature td { text-align: center; vertical-align: top; }
        .signature-label td { height: 20px; font-weight: bold; vertical-align: middle; }
        .signature-box { position: relative; height: 72px; padding-top: 12px; }
        .signature-date { position: absolute; top: 3px; right: 6px; font-size: 8px; color: #374151; }
        .signature-img { max-width: 135px; max-height: 42px; }
        .signature-name { margin-top: 4px; font-weight: bold; }
        .page-break { page-break-before: always; }
        .photo-grid { width: 100%; border-collapse: separate; border-spacing: 8px; }
        .photo-cell { width: 50%; border: 1px solid #111; height: 230px; text-align: center; vertical-align: middle; padding: 6px; }
        .photo-cell img { max-width: 100%; max-height: 205px; }
        .photo-caption { margin-top: 4px; font-size: 9px; color: #374151; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @include('admin.orders.workshop.quality-control.pdf._fabrication-header')

    <div class="section-title">1. Jenis Pengecekan / Dimensi Fabrikasi</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Jenis ukuran Pekerjaan</th>
                <th style="width: 18%;">Sesuai Gambar Teknik / Order</th>
                <th style="width: 18%;">Tidak Sesuai Gambar Teknik / Order</th>
                <th style="width: 25%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dimensionRows as $index => $row)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>{{ $row['item'] ?? '-' }}</td>
                    <td class="check">{{ ($row['status'] ?? '') === 'sesuai' ? '✓' : '' }}</td>
                    <td class="check">{{ ($row['status'] ?? '') === 'tidak_sesuai' ? '✓' : '' }}</td>
                    <td>{{ $row['notes'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">2. Jenis Material Yang Digunakan</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Material Pekerjaan</th>
                <th style="width: 24%;">Jenis Material</th>
                <th style="width: 34%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($materialRows as $index => $row)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>{{ $row['material_work'] ?? '-' }}</td>
                    <td>{{ $row['material_type'] ?? '-' }}</td>
                    <td>{{ $row['notes'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">3. Pengelasan</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Item Pengelasan</th>
                <th style="width: 18%;">Jenis Elektroda</th>
                <th style="width: 14%;">Baik</th>
                <th style="width: 18%;">Perlu perbaikan</th>
                <th style="width: 24%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($weldingRows as $index => $row)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>{{ $row['item'] ?? '-' }}</td>
                    <td>{{ $row['electrode'] ?? '-' }}</td>
                    <td class="check">{{ ($row['condition'] ?? '') === 'baik' ? '✓' : '' }}</td>
                    <td class="check">{{ ($row['condition'] ?? '') === 'perlu_perbaikan' ? '✓' : '' }}</td>
                    <td>{{ $row['notes'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Catatan</div>
    <div class="notes">{{ $payload['notes'] ?? '' }}</div>

    <table class="signature" style="margin-top: 12px;">
        <tr class="signature-label">
            <td>Inspector</td>
            <td>Supervisor</td>
            <td>Menyetujui</td>
        </tr>
        <tr>
            <td>
                <div class="signature-box">
                    @if ($signatureDate !== '')
                        <div class="signature-date">{{ $signatureDate }}</div>
                    @endif
                    @if ($signatureData !== '')
                        <img src="{{ $signatureData }}" class="signature-img" alt="">
                    @endif
                    <div class="signature-name">{{ $signatureName !== '' ? $signatureName : '( ____________________ )' }}</div>
                </div>
            </td>
            <td>
                <div class="signature-box">
                    @if ($approvalSignatureDate($workshopManagerSignature) !== '')
                        <div class="signature-date">{{ $approvalSignatureDate($workshopManagerSignature) }}</div>
                    @endif
                    @if ($approvalSignatureData($workshopManagerSignature) !== '')
                        <img src="{{ $approvalSignatureData($workshopManagerSignature) }}" class="signature-img" alt="">
                    @endif
                    <div class="signature-name">{{ $approvalSignatureName($workshopManagerSignature) !== '' ? $approvalSignatureName($workshopManagerSignature) : '( ____________________ )' }}</div>
                    @if ($approvalSignatureRole($workshopManagerSignature) !== '')
                        <div class="muted">{{ $approvalSignatureRole($workshopManagerSignature) }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="signature-box">
                    @if ($approvalSignatureDate($userManagerSignature) !== '')
                        <div class="signature-date">{{ $approvalSignatureDate($userManagerSignature) }}</div>
                    @endif
                    @if ($approvalSignatureData($userManagerSignature) !== '')
                        <img src="{{ $approvalSignatureData($userManagerSignature) }}" class="signature-img" alt="">
                    @endif
                    <div class="signature-name">{{ $approvalSignatureName($userManagerSignature) !== '' ? $approvalSignatureName($userManagerSignature) : '( ____________________ )' }}</div>
                    @if ($approvalSignatureRole($userManagerSignature) !== '')
                        <div class="muted">{{ $approvalSignatureRole($userManagerSignature) }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @forelse ($beforeFiles->chunk(2)->chunk(2) as $pageRows)
        <div class="page-break">
            @include('admin.orders.workshop.quality-control.pdf._fabrication-header')
            <div class="section-title">Gambar teknik Pekerjaan Fabrikasi / Barang Sebelum Repair</div>
            <table class="photo-grid">
                @foreach ($pageRows as $chunk)
                    <tr>
                        @foreach ($chunk as $file)
                            @php($path = $imagePath($file))
                            <td class="photo-cell">
                                @if ($path)
                                    <img src="{{ $path }}" alt="">
                                @endif
                                <div class="photo-caption">{{ $file->original_name }}</div>
                            </td>
                        @endforeach
                        @if ($chunk->count() === 1)
                            <td class="photo-cell"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @empty
        <div class="page-break">
            @include('admin.orders.workshop.quality-control.pdf._fabrication-header')
            <div class="section-title">Gambar teknik Pekerjaan Fabrikasi / Barang Sebelum Repair</div>
            <table class="photo-grid">
                <tr><td class="photo-cell muted">Belum ada foto.</td><td class="photo-cell"></td></tr>
            </table>
        </div>
    @endforelse

    @forelse ($afterFiles->chunk(2)->chunk(2) as $pageRows)
        <div class="page-break">
            @include('admin.orders.workshop.quality-control.pdf._fabrication-header')
            <div class="section-title">Bukti Pendukung Foto Setelah Fabrikasi / Repair dan QC</div>
            <table class="photo-grid">
                @foreach ($pageRows as $chunk)
                    <tr>
                        @foreach ($chunk as $file)
                            @php($path = $imagePath($file))
                            <td class="photo-cell">
                                @if ($path)
                                    <img src="{{ $path }}" alt="">
                                @endif
                                <div class="photo-caption">{{ $file->original_name }}</div>
                            </td>
                        @endforeach
                        @if ($chunk->count() === 1)
                            <td class="photo-cell"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @empty
        <div class="page-break">
            @include('admin.orders.workshop.quality-control.pdf._fabrication-header')
            <div class="section-title">Bukti Pendukung Foto Setelah Fabrikasi / Repair dan QC</div>
            <table class="photo-grid">
                <tr><td class="photo-cell muted">Belum ada foto.</td><td class="photo-cell"></td></tr>
            </table>
        </div>
    @endforelse
</body>
</html>
