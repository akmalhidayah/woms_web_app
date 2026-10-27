<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cetak Semua QR Peralatan Aktif</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; color: #111827; font-family: Arial, sans-serif; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: center; gap: 12px; padding: 12px; background: rgba(238, 242, 247, .96); }
        .toolbar button, .toolbar a { border: 0; border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #1d4ed8; color: #fff; }
        .toolbar a { border: 1px solid #cbd5e1; background: #fff; color: #334155; }
        .summary { font-size: 13px; color: #475569; }
        .sheet { position: relative; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-template-rows: repeat(2, minmax(0, 1fr)); align-items: center; gap: 6mm 7mm; width: 277mm; height: 190mm; margin: 10mm auto; background: #fff; }
        .sheet::before { position: absolute; inset: 50% 0 auto; border-top: 1px dashed #94a3b8; content: ''; }
        .sheet::after { position: absolute; inset: 0 auto 0 50%; border-left: 1px dashed #94a3b8; content: ''; }
        .label { display: flex; width: 135mm; height: 90mm; min-width: 0; flex-direction: column; justify-self: center; border: 1px solid #0f172a; background: #fff; padding: 5mm; }
        .brand { display: flex; align-items: center; justify-content: space-between; gap: 4mm; border-bottom: 1px solid #111827; padding-bottom: 2mm; }
        .brand-logos { display: flex; flex: 0 0 auto; align-items: center; gap: 2mm; }
        .brand-logo { display: block; width: 12mm; height: 12mm; object-fit: contain; }
        .brand-title { font-size: 9pt; font-weight: 800; letter-spacing: .6px; text-align: right; }
        .content { display: flex; min-height: 0; flex: 1; align-items: center; gap: 6mm; }
        .qr { width: 50mm; height: 50mm; flex: 0 0 auto; object-fit: contain; }
        .info { min-width: 0; }
        .name { overflow-wrap: anywhere; font-size: 16pt; font-weight: 800; line-height: 1.12; }
        .code { margin-top: 3mm; font-size: 14pt; font-weight: 700; }
        .scan { margin-top: 4mm; font-size: 8.5pt; font-weight: 700; line-height: 1.3; }
        .foot { font-size: 7pt; color: #475569; text-align: center; }
        .empty { max-width: 640px; margin: 80px auto; border: 1px solid #cbd5e1; border-radius: 14px; background: #fff; padding: 32px; text-align: center; }
        @media print {
            body { background: #fff; }
            .toolbar, .empty { display: none; }
            .sheet { break-after: page; page-break-after: always; margin: 0; }
            .sheet:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>
</head>
<body data-label-count="{{ $units->count() }}">
    <div class="toolbar">
        <a href="{{ route('admin.inspection-forms.index') }}">Kembali</a>
        <span class="summary">{{ $units->count() }} QR publik aktif · 4 label per lembar A4</span>
        @if ($units->isNotEmpty())
            <button type="button" onclick="window.print()">Cetak Semua QR</button>
        @endif
    </div>

    @forelse ($units->chunk(4) as $pageUnits)
        <section class="sheet" data-label-sheet>
            @foreach ($pageUnits as $unit)
                <article class="label">
                    @include('admin.inspection-units.partials.label-content', ['unit' => $unit])
                </article>
            @endforeach
        </section>
    @empty
        <main class="empty">
            <h1>Belum ada QR publik aktif</h1>
            <p>Aktifkan Master Form, unit peralatan, dan akses QR publik sebelum mencetak label massal.</p>
        </main>
    @endforelse
</body>
</html>
