<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'Laporan' }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #333; }

        /* HEADER / KOP SURAT (Tabel 3 Kolom: Logo - Teks - Logo) */
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header-table td { border: none; padding: 0; vertical-align: middle; }
        .logo-img { height: 100px; max-width: 150px; object-fit: contain; }

        .header-text { text-align: center; }
        .company-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; margin: 0; }
        .company-address { font-size: 10pt; margin: 5px 0; }
        .company-contact { font-size: 9pt; color: #555; }

        /* JUDUL & META */
        h3 { text-align: center; margin-bottom: 5px; text-transform: uppercase; font-size: 14pt; }
        .meta { margin-bottom: 15px; font-size: 9pt; color: #555; text-align: center; }

        /* TABEL DATA */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 9pt; }

        /* UTILS & WARNA */
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .total-row { font-weight: bold; background-color: #eef; }

        /* Badge Status */
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 8pt; color: white; display: inline-block; font-weight: bold; }
        .bg-green { background-color: #10B981; } /* Hijau */
        .bg-yellow { background-color: #F59E0B; color: black; } /* Kuning */
        .bg-red { background-color: #EF4444; } /* Merah */

        /* Text Colors */
        .text-red { color: #DC2626; }
        .text-green { color: #059669; }

        /* Background Cells */
        .bg-in { background-color: #ECFDF5; color: #065F46; }
        .bg-out { background-color: #FEF2F2; color: #991B1B; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td width="15%" class="center">
            @if(isset($settings) && $settings->logo_left)
                <img src="{{ $settings->logoLeftBase64 }}" class="logo-img">
            @endif
        </td>

        <td width="70%" class="header-text">
            <h1 class="company-name">{{ $settings->site_name ?? 'SPBU-N SYSTEM' }}</h1>
            <p class="company-address">{{ $settings->address ?? 'Alamat belum diatur' }}</p>
            <p class="company-contact">
                @if(isset($settings) && $settings->phone) Telp: {{ $settings->phone }} @endif
                @if(isset($settings) && $settings->phone && $settings->public_email) | @endif
                @if(isset($settings) && $settings->public_email) Email: {{ $settings->public_email }} @endif
            </p>
        </td>

        <td width="15%" class="center">
            @if(isset($settings) && $settings->logo_right)
                <img src="{{ $settings->logoRightBase64 }}" class="logo-img">
            @endif
        </td>
    </tr>
</table>

<h3>{{ $title }}</h3>

<div class="meta">
    Periode: {{ $period }} <br>
    Dicetak Oleh: {{ auth()->user()->name }} | Waktu: {{ date('d/m/Y H:i') }}
</div>

@yield('content')

</body>
</html>
