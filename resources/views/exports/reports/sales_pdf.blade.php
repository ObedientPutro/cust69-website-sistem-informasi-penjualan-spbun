<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 9pt; color: #333; }

        /* Kop Surat */
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10pt; }

        /* Meta Data */
        .meta-table { width: 100%; margin-bottom: 15px; font-size: 9pt; }
        .meta-table td { padding: 2px; }
        .text-right { text-align: right; }

        /* Tabel Data */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 8pt; }

        /* Kolom Spesifik */
        .col-money { text-align: right; white-space: nowrap; }
        .col-center { text-align: center; }

        /* Footer Total */
        .total-row { background-color: #e6e6e6; font-weight: bold; }

        /* Utilitas */
        .badge { padding: 2px 4px; border: 1px solid #999; border-radius: 3px; font-size: 7pt; text-transform: uppercase; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { margin-bottom: 2px; font-size: 8.5pt; }
        .item-qty { font-family: monospace; color: #555; }
    </style>
</head>
<body>

<div class="header">
    <h1>SPBU-N [NAMA SPBU ANDA]</h1>
    <p>Alamat Lengkap SPBU, Kota, Provinsi</p>
    <p>Telp: 0812-XXXX-XXXX | Email: admin@spbun.com</p>
</div>

<table class="meta-table">
    <tr>
        <td width="15%"><strong>Laporan:</strong></td>
        <td>Detail Penjualan (Sales)</td>
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
        <th width="12%">Waktu</th>
        <th width="10%">No. Nota</th>
        <th width="20%">Pelanggan</th>
        <th>Detail Produk & Volume</th>
        <th width="10%" class="col-center">Metode</th>
        <th width="10%" class="col-center">Status</th>
        <th width="15%" class="col-money">Total (Rp)</th>
    </tr>
    </thead>
    <tbody>
    @php $grandTotal = 0; @endphp
    @foreach($data as $row)
        @php $grandTotal += $row->grand_total; @endphp
        <tr>
            <td>
                {{ $row->transaction_date->format('d/m/Y') }}<br>
                <small style="color:#666">{{ $row->transaction_date->format('H:i') }}</small>
            </td>
            <td>#{{ $row->id }}</td>
            <td>
                @if($row->customer)
                    <strong>{{ $row->customer->name }}</strong><br>
                    <small>{{ $row->customer->ship_name }}</small>
                @else
                    <em>Umum</em>
                @endif
            </td>
            <td>
                <ul class="item-list">
                    @foreach($row->items as $item)
                        <li>
                            {{ $item->product->name }}
                            <span class="item-qty">({{ $item->quantity_liter }} L)</span>
                        </li>
                    @endforeach
                </ul>
            </td>
            <td class="col-center">
                {{ ucfirst($row->payment_method->value) }}
            </td>
            <td class="col-center">
                @if($row->payment_status->value == 'paid')
                    <span style="color:green; font-weight:bold;">Lunas</span>
                @else
                    <span style="color:red; font-weight:bold;">Belum</span>
                @endif
            </td>
            <td class="col-money">
                {{ number_format($row->grand_total, 0, ',', '.') }}
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="6" class="text-right">GRAND TOTAL PERIODE INI</td>
        <td class="col-money">{{ number_format($grandTotal, 0, ',', '.') }}</td>
    </tr>
    </tfoot>
</table>

</body>
</html>
