<div class="brand">
    <div class="brand-logos">
        <img class="brand-logo" src="{{ asset('assets/branding/logos/logo-sig.png') }}" alt="SIG">
        <img class="brand-logo" src="{{ asset('assets/branding/logos/logo-st2.png') }}" alt="Semen Tonasa">
    </div>
    <div class="brand-title">WOMS — EQUIPMENT INSPECTION</div>
</div>
<div class="content">
    <img class="qr" src="{{ route('admin.inspection-units.qr', $unit) }}" alt="QR Code {{ $unit->asset_code }}">
    <div class="info">
        <div class="name">{{ $unit->form?->name ?: $unit->name }}</div>
        <div class="code">{{ $unit->asset_code }}</div>
        <div class="scan">SCAN UNTUK MELIHAT RIWAYAT PEMERIKSAAN PERALATAN</div>
    </div>
</div>
<div class="foot">Persetujuan inspeksi bukan sertifikasi kelayakan operasi.</div>
