<!DOCTYPE html>
<html>
<head>
    <title>Laporan Laba Rugi</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; }
        .meta { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; }
        th { background-color: #eee; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .text-red { color: #d00; }
    </style>
</head>
<body>
<div class="header">
    <h2>SPBU-N (Nama SPBU Anda)</h2>
    <p>Laporan Laba Rugi (Gross Profit)</p>
</div>

<div class="meta">
    <strong>Periode:</strong> {{ $period }}<br>
    <strong>Dicetak Oleh:</strong> {{ $user }}<br>
    <strong>Tanggal Cetak:</strong> {{ date('d-m-Y H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th>Tanggal</th>
        <th class="right">Omset Penjualan</th>
        <th class="right">HPP (Modal)</th>
        <th class="right">Laba Kotor</th>
    </tr>
    </thead>
    <tbody>
    @php $tOmzet = 0; $tHpp = 0; $tProfit = 0; @endphp
    @foreach($data as $row)
        @php
            $tOmzet += $row['omzet'];
            $tHpp += $row['hpp'];
            $tProfit += $row['gross_profit'];
        @endphp
        <tr>
            <td>{{ date('d/m/Y', strtotime($row['date'])) }}</td>
            <td class="right">{{ number_format($row['omzet'], 0, ',', '.') }}</td>
            <td class="right">({{ number_format($row['hpp'], 0, ',', '.') }})</td>
            <td class="right bold">{{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr style="background-color: #eee;">
        <td class="bold">TOTAL</td>
        <td class="right bold">{{ number_format($tOmzet, 0, ',', '.') }}</td>
        <td class="right bold text-red">({{ number_format($tHpp, 0, ',', '.') }})</td>
        <td class="right bold">{{ number_format($tProfit, 0, ',', '.') }}</td>
    </tr>
    </tfoot>
</table>
</body>
</html>
