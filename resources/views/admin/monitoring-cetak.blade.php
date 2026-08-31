<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Monitoring IKPA - {{ $satker->nama_satker }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; padding: 32px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .sub { color: #64748b; font-size: 12px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .ringkasan { display: flex; gap: 24px; margin: 16px 0 8px; }
        .ringkasan div { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; }
        .label { font-size: 11px; color: #64748b; }
        .value { font-size: 20px; font-weight: 700; }
        .btn-cetak {
            margin-bottom: 20px; padding: 8px 16px; border-radius: 8px; border: none;
            background: #0f172a; color: #fff; font-size: 13px; cursor: pointer;
        }
        @media print {
            .btn-cetak { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="btn-cetak" onclick="window.print()">Cetak / Simpan PDF</button>

    <h1>Laporan Monitoring IKPA — {{ $satker->nama_satker }}</h1>
    <p class="sub">Periode: {{ $labelPeriodeAktif }} &middot; Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</p>

    <div class="ringkasan">
        <div>
            <p class="label">Rata-rata Nilai</p>
            <p class="value">{{ ! is_null($rataRata) ? number_format($rataRata, 2) : '-' }}</p>
        </div>
        <div>
            <p class="label">Jumlah Indikator</p>
            <p class="value">{{ $baris->count() }}</p>
        </div>
        <div>
            <p class="label">Sudah Dinilai</p>
            <p class="value">{{ $baris->whereNotNull('nilai')->count() }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Indikator</th>
                <th>Status</th>
                <th>Nilai</th>
                <th>Catatan Admin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $b)
                <tr>
                    <td>{{ $b['judul'] }}</td>
                    <td>{{ $b['status'] }}</td>
                    <td>{{ !is_null($b['nilai']) ? number_format($b['nilai'], 2) : '-' }}</td>
                    <td>{{ $b['catatan'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada indikator untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>