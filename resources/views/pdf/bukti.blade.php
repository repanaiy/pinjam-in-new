<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Peminjaman {{ $loan->request_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color: #64748b; }
        .head { border-bottom: 2px solid #047857; padding-bottom: 8px; margin-bottom: 14px; }
        .code { font-size: 14px; font-weight: bold; color: #047857; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.info td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.info td.l { width: 32%; color: #64748b; }
        h2 { font-size: 12px; margin: 14px 0 4px; }
        .box { border: 1px solid #cbd5e1; padding: 8px 10px; }
        .sign { width: 100%; margin-top: 36px; }
        .sign td { width: 50%; text-align: center; padding-top: 46px; }
        .rules li { margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="head">
        <h1>Bukti Peminjaman Alat</h1>
        <div class="muted">PINJAM-IN • SMKN 5 Surakarta</div>
        <div class="code">{{ $loan->request_code }}</div>
    </div>

    <h2>Peminjam</h2>
    <table class="info">
        <tr><td class="l">Nama</td><td>{{ $loan->user->name }}</td></tr>
        <tr><td class="l">NIS / Kelas</td><td>{{ $loan->user->nis ?? '—' }} / {{ $loan->user->kelas ?? '—' }}</td></tr>
    </table>

    <h2>Alat</h2>
    <table class="info">
        <tr><td class="l">Nama alat</td><td>{{ $loan->item->name }} ({{ $loan->item->code }})</td></tr>
        <tr><td class="l">Kategori</td><td>{{ $loan->item->category->name }}</td></tr>
        <tr><td class="l">Jumlah</td><td>{{ $loan->quantity }} unit</td></tr>
        @if ($loan->loanUnits->isNotEmpty())<tr><td class="l">Unit diserahkan</td><td>{{ $loan->loanUnits->map(fn ($lu) => $lu->unit->unit_code)->implode(', ') }}</td></tr>@endif
        <tr><td class="l">Periode pinjam</td><td>{{ $loan->periodLabel() }} ({{ $loan->durationDays() }} hari)</td></tr>
        <tr><td class="l">Keperluan</td><td>{{ $loan->purpose }}</td></tr>
        <tr><td class="l">Status</td><td>{{ $loan->status->label() }}</td></tr>
    </table>

    @if ($loan->pickup_date)
        <h2>Jadwal pengambilan</h2>
        <div class="box">
            {{ $loan->pickup_date->translatedFormat('l, j F Y') }}, {{ substr((string) $loan->pickup_time_start, 0, 5) }}–{{ substr((string) $loan->pickup_time_end, 0, 5) }} WIB<br>
            Lokasi: {{ $loan->pickup_location ?? $loan->item->location }}<br>
            Petugas: {{ $loan->reviewer?->name ?? '—' }}
            @if ($loan->admin_note)<br>Catatan: {{ $loan->admin_note }}@endif
        </div>
    @endif

    @if ($loan->returned_at)
        <h2>Pengembalian</h2>
        <div class="box">Dikembalikan {{ $loan->returned_at->translatedFormat('j F Y, H.i') }} WIB — {{ $loan->is_late ? 'terlambat '.$loan->late_days.' hari' : 'tepat waktu' }}.</div>
    @endif

    <h2>Ketentuan</h2>
    <ul class="rules">
        <li>Bawa kartu pelajar asli saat mengambil alat.</li>
        <li>Kembalikan alat paling lambat {{ $loan->end_date->translatedFormat('j F Y') }}.</li>
        <li>Peminjam bertanggung jawab atas kerusakan atau kehilangan alat.</li>
    </ul>

    <table class="sign">
        <tr><td>Peminjam<br><br><br>( {{ $loan->user->name }} )</td><td>Petugas Sarpras<br><br><br>( {{ $loan->reviewer?->name ?? '........................' }} )</td></tr>
    </table>
    <p class="muted" style="margin-top:18px">Dicetak {{ $generatedAt->translatedFormat('j F Y, H.i') }} WIB.</p>
</body>
</html>
