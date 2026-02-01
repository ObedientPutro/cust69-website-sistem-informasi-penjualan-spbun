<!DOCTYPE html>
<html>
<head>
    <title>Laporan Arus Stok</title>
    <style>
        body { font-family: sans-serif; font-size: 9pt; color: #333; }

        /* Kop Surat */
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10pt; }

        /* Meta Data */
        .meta-table { width: 100%; margin-bottom: 15px; font-size: 9pt; }
        .text-right { text-align: right; }

        /* Tabel Data */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 8pt; }

        /* Kolom Spesifik */
        .col-qty { text-align: center; width: 10%; }
        .bg-in { background-color: #e6fffa; color: #047857; } /* Hijau Muda */
        .bg-out { background-color: #fff5f5; color: #c53030; } /* Merah Muda */

        /* Footer */
        .total-row { background-color: #e6e6e6; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>SPBU-N [NAMA SPBU ANDA]</h1>
    <p>Laporan Arus Stok (Inventory Flow)</p>
</div>

<table class="meta-table">
    <tr>
        <td width="15%"><strong>Laporan:</strong></td>
        <td>Mutasi Stok (Masuk & Keluar)</td>
        <td width="15%" class="text-right"><strong>Dicetak Oleh:</strong></td>
        <td width="20%" class="text-right">{{ $user }}</td>
    </tr>
    <tr>
        <td><strong>Periode:</strong></td>
        <td>{{ $period }}</td>
        <td class="text-right"><strong>Waktu Cetak:</strong></td>
        <td class="text-right">{{ date('d/m/Y H:i') }}</td>
    </tr>
</table>

<table class="data-table">
    <thead>
    <tr>
        <th width="15%">Waktu</th>
        <th width="15%">Tipe Mutasi</th>
        <th width="20%">Produk</th>
        <th width="20%">Referensi / Keterangan</th>
        <th class="col-qty bg-in">Masuk</th>
        <th class="col-qty bg-out">Keluar</th>
    </tr>
    </thead>
    <tbody>
    @php $totalIn = 0; $totalOut = 0; @endphp
    @foreach($data as $row)
        @php
            $totalIn += $row['qty_in'];
            $totalOut += $row['qty_out'];
        @endphp
        <tr>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y H:i') }}</td>
            <td>{{ $row['type'] }}</td>
            <td><strong>{{ $row['product_name'] }}</strong></td>
            <td>{{ $row['ref'] }}</td>

            <td class="col-qty {{ $row['qty_in'] > 0 ? 'bg-in' : '' }}">
                {{ $row['qty_in'] > 0 ? $row['qty_in'] : '-' }}
            </td>

            <td class="col-qty {{ $row['qty_out'] > 0 ? 'bg-out' : '' }}">
                {{ $row['qty_out'] > 0 ? $row['qty_out'] : '-' }}
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="4" class="text-right">TOTAL VOLUME</td>
        <td class="col-qty bg-in">{{ $totalIn }}</td>
        <td class="col-qty bg-out">{{ $totalOut }}</td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: right; font-style: italic; padding-top: 10px;">
            Net Change: {{ $totalIn - $totalOut }} Liter
        </td>
    </tr>
    </tfoot>
</table>

</body>
</html>
