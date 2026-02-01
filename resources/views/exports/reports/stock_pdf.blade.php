@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th width="15%">Waktu</th>
            <th width="10%">Tipe</th>
            <th width="25%">Produk</th>
            <th width="20%">Referensi</th>
            <th class="center bg-in">Masuk (In)</th>
            <th class="center bg-out">Keluar (Out)</th>
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
                <td style="font-size: 9pt; color: #555;">{{ $row['ref'] }}</td>

                <td class="center {{ $row['qty_in'] > 0 ? 'bg-in bold' : '' }}">
                    {{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 0, ',', '.') : '-' }}
                </td>

                <td class="center {{ $row['qty_out'] > 0 ? 'bg-out bold' : '' }}">
                    {{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 0, ',', '.') : '-' }}
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="4" class="right">TOTAL VOLUME</td>
            <td class="center bg-in">{{ number_format($totalIn, 0, ',', '.') }}</td>
            <td class="center bg-out">{{ number_format($totalOut, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="6" class="right" style="padding-top: 10px;">
                <strong>Net Change (Selisih): {{ number_format($totalIn - $totalOut, 0, ',', '.') }} Liter</strong>
            </td>
        </tr>
        </tfoot>
    </table>
@endsection
