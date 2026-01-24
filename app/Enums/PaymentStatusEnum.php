<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum PaymentStatusEnum : string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';
    case PENDING = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID => 'Lunas',
            self::UNPAID => 'Belum Lunas',
            self::PENDING => 'Menunggu Konfirmasi',
        };
    }

}
