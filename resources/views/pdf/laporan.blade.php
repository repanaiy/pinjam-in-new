<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Peminjaman</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #64748b; }
        .cards { width: 100%; margin: 12px 0; border-collapse: separate; border-spacing: 6px 0; }
        .cards td { border: 1px solid #cbd5e1; padding: 8px 10px; }
        .cards .n { font-size: 15px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1e293b; color: #fff; text-align: left; padding: 5px 6px; font-size: 8.5px; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; vertical-align: top; }
        .note { margin-top: 8px; color: #b45309; }
    </style>
</head>
<body>
    <h1>PINJAM-IN — Laporan Peminjaman</h1>
    <div class="muted">SMKN 5 Surakarta &bull; {{ $description }}</div>
    <div class="muted">Dicetak {{ $generatedAt->translatedFormat('j F Y, H.i') }} WIB oleh {{ $officer }}</div>

    <table class="cards">
        <tr>
            <td><div class="muted">Total transaksi</div><div class="n">{{ $summary['total'] }}</div></td>
            <td><div class="muted">Disetujui</div><div class="n">{{ $summary['approved'] }}</div></td>
            <td><div class="muted">Ditolak / batal</div><div class="n">{{ $summary['declined'] }}</div></td>
            <td><div class="muted">Rata-rata durasi</div><div class="n">{{ number_format($summary['avg_days'], 1, ',', '.') }} hari</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr><th>Kode</th><th>Peminjam</th><th>Kelas</th><th>Alat</th><th>Jml</th><th>Periode</th><th>Durasi</th><th>Status</th><th>Terlambat</th></tr>
        </thead>
        <tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td>{{ $loan->request_code }}</td>
                    <td>{{ $loan->user->name }}</td>
                    <td>{{ $loan->user->kelas }}</td>
                    <td>{{ $loan->item->name }}</td>
                    <td>{{ $loan->quantity }}</td>
                    <td>{{ $loan->periodLabel() }}</td>
                    <td>{{ $loan->durationDays() }} hari</td>
                    <td>{{ $loan->status->label() }}</td>
                    <td>{{ $loan->is_late ? $loan->late_days.' hari' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">Tidak ada transaksi pada filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($truncated)<div class="note">Menampilkan {{ $limit }} transaksi pertama dari {{ $summary['total'] }}. Gunakan ekspor Excel untuk data lengkap.</div>@endif
</body>
</html>
