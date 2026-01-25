<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: sans-serif; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { padding: 2px 5px; border-radius: 4px; font-size: 8pt; color: white;}
        .bg-green { background-color: #10B981; }
        .bg-red { background-color: #EF4444; }
        .bg-gray { background-color: #6B7280; }
        .total-row { font-weight: bold; background-color: #eef; }
    </style>
</head>
<body>
<h2 class="text-center">Mutasi Transaksi Penjualan</h2>
<p class="text-center">Periode: {{ $period }}</p>

<table>
    <thead>
    <tr>
        <th>Waktu</th>
        <th>ID</th>
        <th>Pelanggan</th>
        <th>Detail Produk</th>
        <th class="text-right">Total (Rp)</th>
        <th>Metode</th>
        <th>Status</th>
        <th>Kasir</th>
    </tr>
    </thead>
    <tbody>
    @foreach($transactions as $trx)
        <tr>
            <td>{{ $trx->transaction_date->format('d/m/Y H:i') }}</td>
            <td>#{{ $trx->id }}</td>
            <td>{{ $trx->customer ? $trx->customer->name : 'Umum' }}</td>
            <td>
                @foreach($trx->items as $item)
                    <div>{{ $item->product->name }} ({{ $item->quantity_liter }}L)</div>
                @endforeach
            </td>
            <td class="text-right">{{ number_format($trx->grand_total, 0, ',', '.') }}</td>
            <td>{{ ucfirst($trx->payment_method->value) }}</td>
            <td>
                @if($trx->payment_status->value === 'paid')
                    <span class="badge bg-green">Lunas</span>
                @else
                    <span class="badge bg-red">Belum Lunas</span>
                @endif
            </td>
            <td>{{ $trx->user->name }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="4" class="text-right">Grand Total</td>
        <td class="text-right">{{ number_format($transactions->sum('grand_total'), 0, ',', '.') }}</td>
        <td colspan="3"></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
