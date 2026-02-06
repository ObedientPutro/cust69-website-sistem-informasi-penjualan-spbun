@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th style="text-align: center" width="2%">No.</th>
            <th style="text-align: center" width="12%">Waktu</th>
            <th style="text-align: center" width="15%">Kode Transaksi</th>
            <th style="text-align: center" width="18%">Pelanggan</th>
            <th style="text-align: center">Detail Produk</th>
            <th style="text-align: center" width="12%">Total (Rp)</th>
            <th style="text-align: center" width="8%">Metode</th>
            <th style="text-align: center" width="10%">Status</th>
            <th style="text-align: center" width="10%">Kasir</th>
        </tr>
        </thead>
        <tbody>
        @foreach($transactions as $trx)
            <tr>
                <td style="text-align: center">{{ $loop->iteration }}</td>
                <td style="text-align: center">{{ $trx->transaction_date->format('d/m/Y H:i') }}</td>

                <td style="font-family: monospace; font-size: 9pt; text-align: center">
                    {{ $trx->trx_code }}
                </td>

                <td>{{ $trx->customer ? $trx->customer->ship_name : 'Umum' }}</td>

                <td>
                    @foreach($trx->items as $item)
                        <div>{{ $item->product->name }} ({{ $item->quantity_liter }}L)</div>
                    @endforeach
                </td>

                <td style="text-align: center">{{ number_format($trx->grand_total, 0, ',', '.') }}</td>

                <td style="text-align: center">{{ ucfirst($trx->payment_method->value) }}</td>

                <td style="text-align: center">
                    @if($trx->payment_status->value === 'paid')
                        <span class="badge bg-green">Lunas</span>
                    @elseif($trx->payment_status->value === 'returned')
                        <span class="badge bg-red">Void / Batal</span>
                    @else
                        <span class="badge bg-yellow">Belum Lunas</span>
                    @endif
                </td>

                <td style="text-align: center">{{ $trx->user->name }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" style="text-align: right">Grand Total (Net)</td>
            <td style="text-align: center">
                {{ number_format($transactions->where('payment_status.value', '!=', 'returned')->sum('grand_total'), 0, ',', '.') }}
            </td>
            <td colspan="3"></td>
        </tr>
        </tfoot>
    </table>
@endsection
