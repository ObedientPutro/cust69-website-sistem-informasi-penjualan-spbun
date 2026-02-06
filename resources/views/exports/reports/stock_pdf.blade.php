@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th width="15%">Waktu</th>
            <th width="10%">Tipe</th>
            <th width="10%">Produk</th>
            <th width="15%">Pelanggan</th>
            <th width="20%">Referensi</th>
            <th style="text-align: center;" class="bg-in">Masuk (In)</th>
            <th style="text-align: center;" class="bg-out">Keluar (Out)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $totalIn = 0;
            $totalOut = 0;
        @endphp

        @foreach($data as $row)
            @php
                $totalIn += $row['qty_in'];
                $totalOut += $row['qty_out'];
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y H:i') }}</td>
                <td>{{ $row['type'] }}</td>
                <td class="bold">{{ $row['product_name'] }}</td>
                <td >{{ $row['customer_name'] }}</td>
                <td style="font-size: 9pt; color: #555;">{{ $row['ref'] }}</td>
                <td style="text-align: center;" class="{{ $row['qty_in'] > 0 ? 'bg-in bold' : '' }}">
                    {{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 0, ',', '.') : '-' }}
                </td>

                <td style="text-align: center;" class="{{ $row['qty_out'] > 0 ? 'bg-out bold' : '' }}">
                    {{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 0, ',', '.') : '-' }}
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="right">TOTAL VOLUME</td>
            <td style="text-align: center;" class="bg-in">{{ number_format($totalIn, 0, ',', '.') }}</td>
            <td style="text-align: center;" class="bg-out">{{ number_format($totalOut, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="7" style="padding-top: 10px; text-align: right">
                <strong>Net Change (Selisih): {{ number_format($totalIn - $totalOut, 0, ',', '.') }} Liter</strong>
            </td>
        </tr>
        </tfoot>
    </table>
@endsection
