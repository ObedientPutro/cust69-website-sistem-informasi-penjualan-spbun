<!DOCTYPE html>
<html>
<head>
    <title>Struk #{{ $trx->trx_code }}</title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 0; padding: 5px; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        .title { font-size: 12px; font-weight: bold; }
        .meta { margin-bottom: 5px; }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .items td { padding: 2px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .total-area { border-top: 1px dashed #000; padding-top: 5px; }
        .footer { text-align: center; margin-top: 10px; font-size: 8px; }
    </style>
</head>
<body>
<div class="header">
    <div class="title">{{ $settings->site_name ?? 'SPBU-N' }}</div>
    <div>{{ $settings->address ?? '-' }}</div>
</div>

<div class="meta">
    No: {{ $trx->trx_code }}<br>
    Tgl: {{ optional($trx->transaction_date)->format('d/m/y H:i') ?? '-' }}<br>
    Kasir: {{ $trx->user->name ?? '-' }}<br>
    Plg: {{ $trx->customer->ship_name ?? 'UMUM' }}
</div>

<table class="items">
    @foreach($trx->items as $item)
        <tr>
            <td colspan="2">{{ $item->product->name }}</td>
        </tr>
        <tr>
            <td>{{ 0 + $item->quantity_liter }}L x {{ number_format($item->price_per_liter) }}</td>
            <td class="text-right">{{ number_format($item->subtotal) }}</td>
        </tr>
    @endforeach
</table>

<div class="total-area">
    <table width="100%">
        <tr>
            <td><strong>TOTAL</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($trx->grand_total) }}</strong></td>
        </tr>
        <tr>
            <td>Metode</td>
            <td class="text-right">{{ ucfirst($trx->payment_method->value ?? '-') }}</td>
        </tr>
        @if(optional($trx->payment_status)->value === 'returned')
            <tr>
                <td colspan="2" class="text-center" style="font-weight:bold; font-size:12px; padding-top:5px;">*** BATAL / VOID ***</td>
            </tr>
        @endif
    </table>
</div>

<div class="footer">
    Terima Kasih<br>
    Simpan struk ini sebagai bukti sah.
</div>
</body>
</html>
