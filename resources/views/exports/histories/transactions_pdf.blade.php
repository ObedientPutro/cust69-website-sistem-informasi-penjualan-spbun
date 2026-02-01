@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th>Waktu</th>
            <th>ID</th>
            <th>Pelanggan</th>
            <th>Detail Produk</th>
            <th class="right">Total (Rp)</th>
            <th class="center">Metode</th>
            <th class="center">Status</th>
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
                <td class="right">{{ number_format($trx->grand_total, 0, ',', '.') }}</td>
                <td class="center">{{ ucfirst($trx->payment_method->value) }}</td>
                <td class="center">
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
            <td colspan="4" class="right">Grand Total</td>
            <td class="right">{{ number_format($transactions->sum('grand_total'), 0, ',', '.') }}</td>
            <td colspan="3"></td>
        </tr>
        </tfoot>
    </table>
@endsection
