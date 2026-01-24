<!DOCTYPE html>
<html>
<head>
    <title>Laporan Audit Tangki</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        h2 { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 15px; font-size: 9pt; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .right { text-align: right; }
        .center { text-align: center; }
        .danger { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
<h2>Laporan Audit Tangki (Sounding)</h2>

<div class="meta">
    Periode: {{ $start_date }} s/d {{ $end_date }} <br>
    Dicetak Oleh: {{ auth()->user()->name }}
</div>

<table>
    <thead>
    <tr>
        <th>Waktu Cek</th>
        <th>Produk</th>
        <th class="center">Tinggi (cm)</th>
        <th class="center">Stok Sistem</th>
        <th class="center">Stok Fisik</th>
        <th class="center">Selisih</th>
        <th>Petugas</th>
    </tr>
    </thead>
    <tbody>
    @foreach($logs as $row)
        <tr>
            <td>{{ $row->recorded_at->format('d-m-Y H:i') }}</td>
            <td>{{ $row->product->name }}</td>
            <td class="center">{{ $row->physical_height_cm ?? '-' }}</td>
            <td class="center">{{ number_format($row->system_liter_snapshot, 0, ',', '.') }}</td>
            <td class="center">{{ number_format($row->physical_liter, 0, ',', '.') }}</td>
            <td class="center {{ $row->difference < 0 ? 'danger' : 'success' }}">
                {{ ($row->difference > 0 ? '+' : '') . number_format($row->difference, 2, ',', '.') }}
            </td>
            <td>{{ $row->user->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
