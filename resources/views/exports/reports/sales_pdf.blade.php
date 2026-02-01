@extends('exports.layouts.pdf_layout')

@section('content')
    <style>
        /* Styling khusus untuk area detail */
        .detail-row td {
            background-color: #f9fafb;
            padding: 10px 20px !important;
            border-top: none;
            border-bottom: 2px solid #ddd;
        }
        .sub-title {
            font-size: 8pt;
            font-weight: bold;
            color: #555;
            margin-bottom: 4px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
            display: block;
        }
        .sub-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8pt;
            background-color: #fff;
        }
        .sub-table th {
            background-color: #eee;
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        .sub-table td {
            border: 1px solid #eee;
            padding: 3px 4px;
            color: #444;
            background-color: #fff !important;
        }
        .empty-msg {
            font-style: italic;
            color: #888;
            font-size: 8pt;
            padding: 5px;
        }
    </style>

    <table class="data-table">
        <thead>
        <tr>
            <th rowspan="2" width="10%">Tanggal</th>
            <th colspan="3" class="center bg-in">Volume (Liter)</th>
            <th colspan="4" class="center">Omset Sistem (Rupiah)</th>
            <th colspan="2" class="center bg-out">Realisasi Fisik</th>
        </tr>
        <tr>
            <th class="center bg-in" width="8%">Fisik</th>
            <th class="center bg-in" width="8%">Sistem</th>
            <th class="center bg-in" width="8%">Selisih</th>
            <th class="right" width="10%">Cash</th>
            <th class="right" width="10%">Trf</th>
            <th class="right" width="10%">Bon</th>
            <th class="right bold" width="12%">Total</th>
            <th class="right bg-out" width="12%">Uang Laci</th>
            <th class="center bg-out" width="12%">Beda Kas</th>
        </tr>
        </thead>
        <tbody>
        @php
            $grandOmset = 0;
            $grandDiff = 0;
        @endphp

        @foreach($data as $row)
            @php
                // PERBAIKAN KEY DISINI (sys_total & diff_liter)
                $grandOmset += $row['sys_total'];
                $grandDiff += $row['diff_liter'];
            @endphp

            <tr style="background-color: #fff;">
                <td><strong>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</strong></td>

                <td class="center">{{ number_format($row['phys_liter'], 0, ',', '.') }}</td>
                <td class="center bold">{{ number_format($row['sys_liter'], 0, ',', '.') }}</td>
                <td class="center {{ $row['diff_liter'] > 5 ? 'text-red bold' : ($row['diff_liter'] < 0 ? 'text-yellow bold' : 'text-green') }}">
                    {{ ($row['diff_liter'] > 0 ? '+' : '') . number_format($row['diff_liter'], 2, ',', '.') }}
                </td>

                <td class="right" style="font-size: 8pt; color: #666;">{{ number_format($row['sys_cash'], 0, ',', '.') }}</td>
                <td class="right" style="font-size: 8pt; color: #666;">{{ number_format($row['sys_transfer'], 0, ',', '.') }}</td>
                <td class="right" style="font-size: 8pt; color: #666;">{{ number_format($row['sys_bon'], 0, ',', '.') }}</td>
                <td class="right bold">{{ number_format($row['sys_total'], 0, ',', '.') }}</td> <td class="right bold">{{ number_format($row['phys_cash'], 0, ',', '.') }}</td>
                <td class="center {{ $row['diff_cash'] < 0 ? 'text-red bold' : 'text-green' }}">
                    {{ number_format($row['diff_cash'], 0, ',', '.') }}
                </td>
            </tr>

            <tr class="detail-row">
                <td colspan="10">

                    <div style="margin-bottom: 10px;">
                        <span class="sub-title">Detail Shift (Fisik Mesin)</span>
                        @if(count($row['shifts']) > 0)
                            <table class="sub-table">
                                <thead>
                                <tr>
                                    <th width="25%">Produk</th>
                                    <th class="right" width="15%">Awal</th>
                                    <th class="right" width="15%">Akhir</th>
                                    <th class="right" width="15%">Terjual (L)</th>
                                    <th class="right">Uang Laci (Rp)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($row['shifts'] as $s)
                                    <tr>
                                        <td>{{ $s->product->name ?? '-' }}</td>
                                        <td class="right">{{ number_format($s->opening_totalizer, 1, ',', '.') }}</td>
                                        <td class="right">{{ number_format($s->closing_totalizer, 1, ',', '.') }}</td>
                                        <td class="right bold">{{ number_format($s->total_sales_liter, 2, ',', '.') }}</td>
                                        <td class="right">{{ number_format($s->cash_collected, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="empty-msg">- Tidak ada data shift -</div>
                        @endif
                    </div>

                    <div>
                        <span class="sub-title">Detail Transaksi (Sistem)</span>
                        @if(count($row['transactions']) > 0)
                            <table class="sub-table">
                                <thead>
                                <tr>
                                    <th width="10%">Jam</th>
                                    <th width="15%">No. Nota</th>
                                    <th width="20%">Pelanggan</th>
                                    <th width="30%">Produk</th>
                                    <th width="10%">Metode</th>
                                    <th class="right">Total (Rp)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($row['transactions'] as $t)
                                    <tr>
                                        <td>{{ $t->transaction_date->format('H:i') }}</td>
                                        <td>#{{ $t->id }}</td>
                                        <td>{{ $t->customer->name ?? 'Umum' }}</td>
                                        <td>
                                            @foreach($t->items as $item)
                                                {{ $item->product->name }} ({{ $item->quantity_liter }}L)<br>
                                            @endforeach
                                        </td>
                                        <td>{{ ucfirst($t->payment_method->value) }}</td>
                                        <td class="right">{{ number_format($t->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="empty-msg">- Tidak ada transaksi sistem -</div>
                        @endif
                    </div>

                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="3" class="right">TOTAL PERIODE</td>
            <td class="center {{ $grandDiff < -5 ? 'text-red' : '' }}">
                {{ number_format($grandDiff, 2, ',', '.') }} L
            </td>
            <td colspan="3"></td>
            <td class="right">Rp {{ number_format($grandOmset, 0, ',', '.') }}</td>
            <td colspan="2"></td>
        </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; font-size: 8pt; color: #666; font-style: italic; border: 1px dashed #ccc; padding: 10px;">
        <strong>Catatan Laporan:</strong><br>
        1. Data <strong>Fisik</strong> diambil dari laporan Shift Operator (Totalisator & Uang di Laci).<br>
        2. Data <strong>Sistem</strong> diambil dari input Transaksi Kasir.<br>
        3. Selisih Liter > 5L akan ditandai Merah (Perlu investigasi).
    </div>
@endsection
