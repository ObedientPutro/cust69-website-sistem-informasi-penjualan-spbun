@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th width="12%">Waktu</th>
            <th width="15%">Kode Transaksi</th> <th width="18%">Pelanggan</th>
            <th>Detail Produk</th>
            <th class="right" width="12%">Total (Rp)</th>
            <th class="center" width="8%">Metode</th>
            <th class="center" width="10%">Status</th>
            <th width="10%">Kasir</th>
        </tr>
        </thead>
        <tbody>
        @foreach($transactions as $trx)
            <tr>
                <td>{{ $trx->transaction_date->format('d/m/Y H:i') }}</td>

                <td style="font-family: monospace; font-size: 9pt;">
                    {{ $trx->trx_code }}
                </td>

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
                    @elseif($trx->payment_status->value === 'returned')
                        <span class="badge bg-red">Void / Batal</span>
                    @else
                        <span class="badge bg-yellow">Belum Lunas</span>
                    @endif
                </td>

                <td>{{ $trx->user->name }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="4" class="right">Grand Total (Net)</td>
            <td class="right">
                {{ number_format($transactions->where('payment_status.value', '!=', 'returned')->sum('grand_total'), 0, ',', '.') }}
            </td>
            <td colspan="3"></td>
        </tr>
        </tfoot>
    </table>
@endsection
