@extends('exports.layouts.pdf_layout')

@section('content')
    <table class="data-table">
        <thead>
        <tr>
            <th>Waktu Cek</th>
            <th>Produk</th>
            <th class="center">Tinggi (cm)</th>
            <th class="center">Stok Sistem</th>
            <th class="center">Stok Fisik</th>
            <th class="center">Selisih</th>
            <th>Petugas</th>
        </tr>
        </thead>
        <tbody>
        @foreach($logs as $row)
            <tr>
                <td>{{ $row->recorded_at->format('d-m-Y H:i') }}</td>
                <td>{{ $row->product->name }}</td>
                <td class="center">{{ $row->physical_height_cm ?? '-' }}</td>
                <td class="center">{{ number_format($row->system_liter_snapshot, 0, ',', '.') }}</td>
                <td class="center">{{ number_format($row->physical_liter, 0, ',', '.') }}</td>
                <td class="center {{ $row->difference < 0 ? 'danger' : 'success' }}">
                    {{ ($row->difference > 0 ? '+' : '') . number_format($row->difference, 2, ',', '.') }}
                </td>
                <td>{{ $row->user->name }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
