<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum ShipTypeEnum : string
{
    use EnumHelper;

    case FISHING = 'fishing';
    case CARRIER = 'carrier';
    case SUPPORT = 'support';
    case SMALL_BOAT = 'small_boat';
    case TRANSPORT = 'transport';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::FISHING => 'Kapal Penangkap Ikan',
            self::CARRIER => 'Kapal Pengangkut Ikan',
            self::SUPPORT => 'Kapal Pendukung / Lampu',
            self::SMALL_BOAT => 'Perahu Motor Tempel (Kecil)',
            self::TRANSPORT => 'Kapal Transportasi / Barang',
            self::OTHER => 'Lainnya',
        };
    }
}
