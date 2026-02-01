@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th width="20%">Tanggal</th>
            <th class="right">Omset Penjualan</th>
            <th class="right">HPP (Modal)</th>
            <th class="right">Laba Kotor</th>
        </tr>
        </thead>
        <tbody>
        @php
            $tOmzet = 0;
            $tHpp = 0;
            $tProfit = 0;
        @endphp

        @foreach($data as $row)
            @php
                $tOmzet += $row['omzet'];
                $tHpp += $row['hpp'];
                $tProfit += $row['gross_profit'];
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                <td class="right">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                <td class="right text-red">(Rp {{ number_format($row['hpp'], 0, ',', '.') }})</td>
                <td class="right bold text-green">Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td>TOTAL AKUMULASI</td>
            <td class="right">{{ number_format($tOmzet, 0, ',', '.') }}</td>
            <td class="right text-red">({{ number_format($tHpp, 0, ',', '.') }})</td>
            <td class="right text-green" style="font-size: 11pt;">Rp {{ number_format($tProfit, 0, ',', '.') }}</td>
        </tr>
        </tfoot>
    </table>
@endsection
