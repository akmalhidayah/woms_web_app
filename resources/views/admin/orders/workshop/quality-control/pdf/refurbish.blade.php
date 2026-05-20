@php
    $repairRows = collect($payload['repair_descriptions'] ?? []);
    $spareRows = collect($payload['spare_parts'] ?? []);
    $testRows = collect($payload['commissioning_tests'] ?? []);
    $repairFiles = collect($filesByCategory->get('refurbish_repair', collect()));
    $spareFiles = collect($filesByCategory->get('refurbish_sparepart', collect()));
    $testFiles = collect($filesByCategory->get('refurbish_commissioning', collect()));
    $notesRows = function (string $rowsKey, string $legacyKey) use ($payload) {
        $rows = collect($payload[$rowsKey] ?? [])
            ->filter(fn ($row): bool => is_array($row) && trim((string) ($row['note'] ?? '')) !== '')
            ->values();

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        $legacy = trim((string) ($payload[$legacyKey] ?? ''));

        if ($legacy === '') {
            return collect();
        }

        return collect(preg_split('/\r\n|\r|\n/', $legacy))
            ->map(fn ($note): array => ['note' => trim((string) $note)])
            ->filter(fn (array $row): bool => $row['note'] !== '')
            ->values();
    };
    $notesBeforeRows = $notesRows('notes_before_rows', 'notes_before');
    $notesAfterRows = $notesRows('notes_after_rows', 'notes_after');
    $notesCount = max($notesBeforeRows->count(), $notesAfterRows->count());
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
    $stLogo = str_replace('\\', '/', public_path('assets/branding/logos/logo-st2.png'));
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16px 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #4b5563; padding: 4px 5px; vertical-align: top; }
        th { background: #d9ead3; font-weight: bold; text-align: center; }
        .top td { border: 1px solid #111; }
        .logo { height: 42px; max-width: 95px; }
        .company { text-align: center; font-weight: bold; font-size: 12px; line-height: 1.45; }
        .title { position: relative; margin: 7px 0; border: 1px solid #166534; background: #fff; color: #16a34a; padding: 6px; text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .title-report { position: absolute; top: 7px; right: 8px; color: #111827; font-size: 8px; font-weight: bold; letter-spacing: 0; text-align: right; }
        .section { background: #d9eaf7; border: 1px solid #4b5563; padding: 5px 6px; font-weight: bold; text-transform: uppercase; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .photo { border: 1px solid #4b5563; height: 88px; text-align: center; vertical-align: middle; padding: 4px; }
        .photo img { max-width: 100%; max-height: 76px; }
        .muted { color: #6b7280; }
        .signature td { text-align: center; vertical-align: top; }
        .signature-label td { height: 20px; font-weight: bold; vertical-align: middle; }
        .signature-box { position: relative; height: 66px; padding-top: 12px; }
        .signature-date { position: absolute; top: 3px; right: 6px; font-size: 8px; color: #374151; }
        .signature-img { max-width: 130px; max-height: 38px; }
        .signature-name { margin-top: 4px; font-weight: bold; }
    </style>
</head>
<body>
    @include('admin.orders.workshop.quality-control.pdf._refurbish-header')

    <table style="margin-top: 7px;">
        <tr>
            <td style="width: 60%;">
                <div class="section">Deskripsi Perbaikan</div>
                <table>
                    <thead><tr><th style="width: 8%;">No</th><th>Deskripsi Perbaikan</th></tr></thead>
                    <tbody>
                        @forelse ($repairRows as $index => $row)
                            <tr><td style="text-align:center;">{{ $index + 1 }}</td><td>{{ $row['description'] ?? '' }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <div class="section">Foto Perbaikan</div>
                <table class="grid">
                    @foreach ($repairFiles->take(4)->chunk(2) as $chunk)
                        <tr>
                            @foreach ($chunk as $file)
                                @php($path = $imagePath($file))
                                <td class="photo">@if($path)<img src="{{ $path }}" alt="">@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($repairFiles->isEmpty())<tr><td class="photo muted">Belum ada foto.</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 7px;">
        <tr>
            <td style="width: 60%;">
                <div class="section">Spare Part</div>
                <table>
                    <thead><tr><th>Spare part</th><th style="width: 24%;">Tanggal diterima</th><th style="width: 24%;">Install</th></tr></thead>
                    <tbody>
                        @forelse ($spareRows as $row)
                            <tr><td>{{ $row['name'] ?? '' }}</td><td>{{ $row['received_date'] ?? '' }}</td><td>{{ $row['install'] ?? '' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <div class="section">Foto Sparepart</div>
                <table class="grid">
                    @foreach ($spareFiles->take(4)->chunk(2) as $chunk)
                        <tr>
                            @foreach ($chunk as $file)
                                @php($path = $imagePath($file))
                                <td class="photo">@if($path)<img src="{{ $path }}" alt="">@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($spareFiles->isEmpty())<tr><td class="photo muted">Belum ada foto.</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 7px;">
        <tr>
            <td style="width: 60%;">
                <div class="section">Commissioning Test</div>
                <table>
                    <thead><tr><th>Item test</th><th style="width: 24%;">Tanggal</th><th style="width: 30%;">Kondisi</th></tr></thead>
                    <tbody>
                        @forelse ($testRows as $row)
                            <tr><td>{{ $row['item'] ?? '' }}</td><td>{{ $row['date'] ?? '' }}</td><td>{{ $row['condition'] ?? '' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <div class="section">Foto Commissioning Test</div>
                <table class="grid">
                    @foreach ($testFiles->take(4)->chunk(2) as $chunk)
                        <tr>
                            @foreach ($chunk as $file)
                                @php($path = $imagePath($file))
                                <td class="photo">@if($path)<img src="{{ $path }}" alt="">@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($testFiles->isEmpty())<tr><td class="photo muted">Belum ada foto.</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top: 7px;">
        <tr>
            <th style="width: 8%;">No</th>
            <th>Catatan standar / sebelum</th>
            <th>Setelah penyetelan</th>
        </tr>
        @if ($notesCount > 0)
            @for ($index = 0; $index < $notesCount; $index++)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="height: 24px;">{{ $notesBeforeRows->get($index)['note'] ?? '' }}</td>
                    <td>{{ $notesAfterRows->get($index)['note'] ?? '' }}</td>
                </tr>
            @endfor
        @else
            <tr>
                <td colspan="3" class="muted">Belum ada catatan.</td>
            </tr>
        @endif
    </table>

    <table class="signature" style="margin-top: 8px;">
        <tr class="signature-label">
            <td>Diterima oleh User</td>
            <td>Diperiksa oleh Supervisor Of Refurbish</td>
            <td>Mengetahui Manager Workshop Machine</td>
        </tr>
        <tr>
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
        </tr>
    </table>
</body>
</html>
