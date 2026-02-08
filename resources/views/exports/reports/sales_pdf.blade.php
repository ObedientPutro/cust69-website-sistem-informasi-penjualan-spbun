@extends('exports.layouts.pdf_layout')

@section('content')
    <style>
        /* --- RESET & BASE --- */
        body { font-family: sans-serif; color: #333; font-size: 9pt; }

        /* --- CONTAINER PER HARI --- */
        .daily-wrapper {
            width: 100%;
            margin-bottom: 0;
            page-break-inside: auto;
        }

        /* --- 1. TABEL RINGKASAN (HEADER) --- */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border: 1px solid #9ca3af;
        }

        .summary-table th {
            font-size: 8pt; font-weight: bold; text-transform: uppercase;
            border: 1px solid #9ca3af; padding: 6px;
            vertical-align: middle;
        }

        .summary-table td {
            border-left: 1px solid #9ca3af;
            border-right: 1px solid #9ca3af;
            border-top: 1px solid #d1d5db;
            border-bottom: none;
            padding: 8px 5px;
            vertical-align: middle;
            background-color: #fff;
        }

        /* Warna Header */
        .bg-header-date { background-color: #f3f4f6; }
        .bg-header-vol  { background-color: #eff6ff; color: #1e40af; }
        .bg-header-money{ background-color: #f0fdf4; color: #166534; }
        .bg-header-phys { background-color: #fff7ed; color: #9a3412; }
        .bg-header-backdate { background-color: #fefce8; color: #854d0e; } /* Kuning Backdate */

        /* --- 2. CONTAINER DETAIL --- */
        .detail-container {
            width: 100%;
            box-sizing: border-box;
            border-left: 1px solid #9ca3af;
            border-right: 1px solid #9ca3af;
            border-bottom: 2px solid #9ca3af;
            border-top: none;
            background-color: #fcfcfc;
            padding: 15px 0;
            page-break-inside: auto;
        }

        /* --- NESTED TABLES --- */
        .nested-title {
            font-size: 8pt; font-weight: bold; color: #555;
            text-transform: uppercase; display: block;
            padding-left: 10px;
            margin-bottom: 5px; margin-top: 15px;
        }
        .nested-title:first-child { margin-top: 0; }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            font-size: 8pt;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 0;
            page-break-inside: auto;
        }

        .nested-table th {
            background-color: #f9fafb; color: #4b5563; font-weight: bold;
            padding: 6px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .nested-table tr { page-break-inside: auto; }

        .nested-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            vertical-align: middle;
        }

        /* Style khusus baris backdate di detail */
        .row-backdate td {
            background-color: #fefce8; /* Kuning tipis */
            color: #854d0e;
        }

        /* --- UTILITIES --- */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-red { color: #dc2626; }
        .text-green { color: #16a34a; }
        .text-blue { color: #2563eb; }
        .bg-audit { background-color: #fffbeb; color: #92400e; font-style: italic; border-bottom: 1px solid #fcd34d; }
        .page-break { page-break-after: always; display: block; height: 0; clear: both; }

        /* Total Table */
        .total-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            border: 2px solid #4b5563; background-color: #f3f4f6;
        }
        .total-table td { padding: 10px; font-weight: bold; font-size: 10pt; }
    </style>

    {{-- JUDUL LAPORAN --}}
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="margin:0; text-transform: uppercase;">Laporan Detail Penjualan & Setoran</h2>
        <p style="margin:5px 0; font-size: 9pt; color: #666;">
            Periode: {{ \Carbon\Carbon::parse($start_date ?? date('Y-m-d'))->format('d/m/Y') }}
            s/d {{ \Carbon\Carbon::parse($end_date ?? date('Y-m-d'))->format('d/m/Y') }}
        </p>
    </div>

    @php
        $grandOmset = 0;
        $grandDiffLiter = 0;
        $grandDiffCash = 0;
        $grandBackdate = 0; // Tambah variabel grand total backdate
    @endphp

    @foreach($data as $row)
        @php
            $grandOmset += $row['sys_total'];
            $grandDiffLiter += $row['diff_liter'];
            $grandDiffCash += $row['diff_cash'];
            $grandBackdate += ($row['sys_backdate'] ?? 0);
        @endphp

        {{-- WRAPPER PER HARI --}}
        <div class="daily-wrapper">

            {{-- BAGIAN 1: TABEL RINGKASAN --}}
            <table class="summary-table">
                <thead>
                <tr>
                    <th rowspan="2" class="bg-header-date text-center" width="10%">Tanggal</th>
                    <th colspan="3" class="bg-header-vol text-center">Volume (Liter)</th>
                    <th colspan="5" class="bg-header-money text-center">Omset Sistem (Rupiah)</th>
                    <th colspan="2" class="bg-header-phys text-center">Realisasi Fisik</th>
                </tr>
                <tr>
                    <th class="bg-header-vol text-center" width="8%">Fisik</th>
                    <th class="bg-header-vol text-center" width="8%">Sistem</th>
                    <th class="bg-header-vol text-center" width="8%">Selisih</th>

                    <th class="bg-header-money text-right" width="8%">Cash</th>
                    <th class="bg-header-money text-right" width="8%">Trf</th>
                    <th class="bg-header-money text-right" width="8%">Bon</th>
                    <th class="bg-header-backdate text-right" width="9%">Backdate</th>

                    <th class="bg-header-money text-right" width="10%">Total</th>
                    <th class="bg-header-phys text-right" width="10%">Uang Laci</th>
                    <th class="bg-header-phys text-center" width="10%">Beda Kas</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="text-center" style="background-color: #f9fafb;">
                        <strong>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</strong>
                    </td>
                    <td class="text-center">{{ number_format($row['phys_liter'], 0, ',', '.') }}</td>
                    <td class="text-center font-bold">{{ number_format($row['sys_liter'], 0, ',', '.') }}</td>
                    <td class="text-center font-bold {{ $row['diff_liter'] != 0 ? ($row['diff_liter'] > 0 ? 'text-blue' : 'text-red') : 'text-green' }}">
                        {{ $row['diff_liter'] > 0 ? '+' : '' }}{{ number_format($row['diff_liter'], 2, ',', '.') }}
                    </td>

                    <td class="text-right">{{ number_format($row['sys_cash'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['sys_transfer'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['sys_bon'], 0, ',', '.') }}</td>

                    <td class="text-right" style="color: #854d0e; background-color: #fefce8;">
                        {{ ($row['sys_backdate'] ?? 0) > 0 ? number_format($row['sys_backdate'], 0, ',', '.') : '-' }}
                    </td>

                    <td class="text-right font-bold">{{ number_format($row['sys_total'], 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($row['phys_cash'], 0, ',', '.') }}</td>
                    <td class="text-center font-bold {{ $row['diff_cash'] < 0 ? 'text-red' : 'text-green' }}">
                        {{ number_format($row['diff_cash'], 0, ',', '.') }}
                    </td>
                </tr>
                </tbody>
            </table>

            {{-- BAGIAN 2: DETAIL CONTAINER --}}
            <div class="detail-container">

                {{-- DETAIL SHIFT --}}
                <span class="nested-title">Detail Shift (Fisik Mesin)</span>
                <table class="nested-table">
                    <thead>
                    <tr>
                        <th width="25%" class="text-left">Produk</th>
                        <th width="20%" class="text-right">Meter Awal</th>
                        <th width="20%" class="text-right">Meter Akhir</th>
                        <th width="15%" class="text-right">Terjual (L)</th>
                        <th width="20%" class="text-right">Uang Laci</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($row['shifts'] as $s)
                        <tr>
                            <td class="text-left">{{ $s->product->name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($s->opening_totalizer, 1, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($s->closing_totalizer, 1, ',', '.') }}</td>
                            <td class="text-right font-bold">{{ number_format($s->total_sales_liter, 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($s->cash_collected, 0, ',', '.') }}</td>
                        </tr>
                        @if($s->is_audited && $s->owner_note)
                            <tr>
                                <td colspan="5" class="bg-audit text-left">
                                    <strong>[AUDIT OWNER]:</strong> {{ $s->owner_note }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="text-center" style="font-style:italic; color:#999;">Tidak ada data shift</td></tr>
                    @endforelse
                    </tbody>
                </table>

                {{-- DETAIL TRANSAKSI --}}
                <span class="nested-title">Detail Transaksi (Sistem)</span>
                <table class="nested-table">
                    <thead>
                    <tr>
                        <th width="10%" class="text-center">Jam</th>
                        <th width="25%" class="text-left">Pelanggan</th>
                        <th width="35%" class="text-left">Produk</th>
                        <th width="10%" class="text-center">Metode</th>
                        <th width="20%" class="text-right">Total (Rp)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($row['transactions'] as $t)
                        {{-- Tandai baris jika Backdate --}}
                        <tr class="{{ $t->is_backdate ? 'row-backdate' : '' }}">
                            <td class="text-center">
                                {{ $t->transaction_date->format('H:i') }}
                                @if($t->is_backdate)
                                    <br><span style="font-size: 6pt; font-weight:bold; background-color: #fcd34d; padding: 1px 3px; border-radius: 3px;">BKD</span>
                                @endif
                            </td>
                            <td class="text-left">
                                <strong>{{ $t->customer->ship_name ?? 'Umum' }}</strong>
                                @if(isset($t->customer->owner_name))
                                    <br><span style="font-size: 7pt; color: #666;">{{ $t->customer->owner_name }}</span>
                                @endif
                            </td>
                            <td class="text-left">
                                @foreach($t->items as $item)
                                    <div>{{ $item->product->name }} ({{ number_format($item->quantity_liter, 0) }}L)</div>
                                @endforeach
                            </td>
                            <td class="text-center" style="text-transform:uppercase; font-size:7pt; font-weight:bold;">
                                {{ $t->payment_method->value }}
                            </td>
                            <td class="text-right">{{ number_format($t->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center" style="font-style:italic; color:#999;">Tidak ada transaksi sistem</td></tr>
                    @endforelse
                    </tbody>
                </table>

            </div>

        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach

    {{-- GRAND TOTAL --}}
    <table class="total-table">
        <tr>
            <td width="30%" class="text-right">TOTAL PERIODE INI</td>
            <td width="20%" class="text-center {{ $grandDiffLiter != 0 ? 'text-red' : 'text-green' }}">
                Selisih Volume: {{ number_format($grandDiffLiter, 2, ',', '.') }} L
            </td>
            <td width="50%" class="text-right">
                @if($grandBackdate > 0)
                    <span style="font-size: 8pt; font-weight: normal; margin-right: 10px;">(Backdate: {{ number_format($grandBackdate, 0, ',', '.') }})</span>
                @endif
                Total Omset: Rp {{ number_format($grandOmset, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-size: 8pt; color: #666; font-style: italic; border: 1px dashed #ccc; padding: 10px;">
        <strong>Catatan Laporan:</strong><br>
        1. Laporan ini menampilkan <strong>Penjualan Bersih (Net Sales)</strong>. Transaksi "Return/Void" tidak dihitung.<br>
        2. Data <strong>Fisik</strong> diambil dari laporan Shift Operator (Totalisator & Uang di Laci).<br>
        3. Data <strong>Sistem</strong> diambil dari input Transaksi Kasir.<br>
        4. <strong>Backdate:</strong> Transaksi inputan susulan (lampau). Nilainya tidak dihitung dalam "Beda Kas" harian karena bukan tanggung jawab operator shift hari ini.
    </div>
@endsection
