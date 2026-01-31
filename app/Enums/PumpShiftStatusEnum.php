<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum PumpShiftStatusEnum: string
{
    use EnumHelper;

    case OPEN = 'open';
    case CLOSED = 'closed';
    case AUDITED = 'audited';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'Sedang Berjalan (Open)',
            self::CLOSED => 'Tutup Buku (Closed)',
            self::AUDITED => 'Selesai (Audited)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::CLOSED => 'warning',
            self::AUDITED => 'primary',
        };
    }
}
