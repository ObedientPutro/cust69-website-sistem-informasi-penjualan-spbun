<!DOCTYPE html>
<html>
<head>
    <title>Laporan Restock DO</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        h2 { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 15px; font-size: 9pt; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row { font-weight: bold; background-color: #eef; }
    </style>
</head>
<body>
<h2>Laporan Penebusan DO (Restock)</h2>

<div class="meta">
    Periode: {{ $start_date }} s/d {{ $end_date }} <br>
    Dicetak Oleh: {{ auth()->user()->name }} <br>
    Tanggal Cetak: {{ date('d-m-Y H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th>Tanggal</th>
        <th>No. DO / Ref</th>
        <th>Produk</th>
        <th class="center">Volume (L)</th>
        <th class="right">Total (Rp)</th>
        <th>Admin</th>
    </tr>
    </thead>
    <tbody>
    @foreach($logs as $row)
        <tr>
            <td>{{ $row->date->format('d-m-Y') }}</td>
            <td>{{ $row->note ?? '-' }}</td>
            <td>{{ $row->product->name }}</td>
            <td class="center">{{ number_format($row->volume_liter, 0, ',', '.') }}</td>
            <td class="right">Rp {{ number_format($row->total_cost, 0, ',', '.') }}</td>
            <td>{{ $row->user->name }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="3" class="right">Total</td>
        <td class="center">{{ number_format($logs->sum('volume_liter'), 0, ',', '.') }}</td>
        <td class="right">Rp {{ number_format($logs->sum('total_cost'), 0, ',', '.') }}</td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
