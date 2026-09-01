<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kredensial Satker - Sikoor Polda Sumbar</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; padding: 32px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .sub { color: #64748b; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 9px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; background: #f8fafc; }
        .catatan {
            margin-top: 20px; padding: 12px 16px; border-radius: 8px;
            background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-size: 13px; font-weight: 600;
        }
        .toolbar { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn {
            padding: 9px 16px; border-radius: 8px; border: none; font-size: 13px; cursor: pointer; font-weight: 500;
        }
        .btn-print { background: #0f172a; color: #fff; }
        .btn-excel { background: #10b981; color: #fff; }
        @media print {
            .toolbar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="btn btn-excel" onclick="unduhCsv()">Download Excel (CSV)</button>
    </div>

    <h1>Kredensial Login Satker — Sikoor Polda Sumbar</h1>
    <p class="sub">Dicetak: {{ $waktuCetak->translatedFormat('d F Y, H:i') }} WIB &middot; Total {{ $hasil->count() }} akun</p>

    <table id="tabelKredensial">
        <thead>
            <tr>
                <th>Nama Satker</th>
                <th>Username</th>
                <th>Password</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($hasil as $row)
                <tr>
                    <td>{{ $row['nama_satker'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['password'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Tidak ada satker dengan akun login.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="catatan">
        &#9888; Tolong ganti password setelah login pertama kali.
    </p>

    <script>
        function unduhCsv() {
            const rows = [['Nama Satker', 'Username', 'Password']];
            document.querySelectorAll('#tabelKredensial tbody tr').forEach(tr => {
                const cols = Array.from(tr.querySelectorAll('td')).map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`);
                if (cols.length === 3) rows.push(cols);
            });

            const csvContent = rows.map(r => r.join(',')).join('\r\n');
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'kredensial-satker-{{ $waktuCetak->format('Y-m-d') }}.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
