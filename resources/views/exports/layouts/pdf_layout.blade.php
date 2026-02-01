<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'Laporan' }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }

        /* HEADER / KOP */
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header-table td { border: none; padding: 0; vertical-align: middle; }
        .logo-img { height: 70px; max-width: 100px; object-fit: contain; }
        .header-text { text-align: center; }
        .company-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; margin: 0; }
        .company-address { font-size: 10pt; margin: 5px 0; }
        .company-contact { font-size: 9pt; color: #555; }

        /* CONTENT */
        h3 { text-align: center; margin-bottom: 5px; text-transform: uppercase; }
        .meta { margin-bottom: 15px; font-size: 9pt; color: #555; text-align: center; }

        /* DATA TABLE */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .data-table th { background-color: #f2f2f2; }

        /* UTILITIES */
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row { font-weight: bold; background-color: #eef; }
        .badge { padding: 2px 5px; border-radius: 4px; font-size: 8pt; color: white; display: inline-block;}
        .bg-green { background-color: #10B981; }
        .bg-red { background-color: #EF4444; }
        .danger { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td width="15%" class="center">
            @if(isset($settings) && $settings->logo_left)
                <img src="{{ public_path('storage/' . $settings->logo_left) }}" class="logo-img">
            @endif
        </td>
        <td width="70%" class="header-text">
            <h1 class="company-name">{{ $settings->site_name ?? 'SPBU-N SYSTEM' }}</h1>
            <p class="company-address">{{ $settings->address ?? 'Alamat belum diatur' }}</p>
            <p class="company-contact">
                @if(isset($settings) && $settings->phone) Telp: {{ $settings->phone }} | @endif
                @if(isset($settings) && $settings->public_email) Email: {{ $settings->public_email }} @endif
            </p>
        </td>
        <td width="15%" class="center">
            @if(isset($settings) && $settings->logo_right)
                <img src="{{ public_path('storage/' . $settings->logo_right) }}" class="logo-img">
            @endif
        </td>
    </tr>
</table>

<h3>{{ $title }}</h3>

<div class="meta">
    Periode: {{ $period }} <br>
    Dicetak Oleh: {{ auth()->user()->name }} | Tanggal Cetak: {{ date('d-m-Y H:i') }}
</div>

@yield('content')

</body>
</html>
