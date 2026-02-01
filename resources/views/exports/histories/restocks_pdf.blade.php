@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th>Tanggal</th>
            <th>No. DO / Ref</th>
            <th>Produk</th>
            <th class="center">Volume (L)</th>
            <th class="right">Total (Rp)</th>
            <th>Admin</th>
        </tr>
        </thead>
        <tbody>
        @foreach($logs as $row)
            <tr>
                <td>{{ $row->date->format('d-m-Y') }}</td>
                <td>{{ $row->note ?? '-' }}</td>
                <td>{{ $row->product->name }}</td>
                <td class="center">{{ number_format($row->volume_liter, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($row->total_cost, 0, ',', '.') }}</td>
                <td>{{ $row->user->name }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="3" class="right">Total</td>
            <td class="center">{{ number_format($logs->sum('volume_liter'), 0, ',', '.') }}</td>
            <td class="right">Rp {{ number_format($logs->sum('total_cost'), 0, ',', '.') }}</td>
            <td></td>
        </tr>
        </tfoot>
    </table>
@endsection
