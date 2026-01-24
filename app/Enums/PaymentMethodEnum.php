<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum PaymentMethodEnum : string
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case BON = 'bon';

    public function getLabel(): string
    {
        return match ($this) {
            self::CASH => 'Tunai (Cash)',
            self::TRANSFER => 'Transfer Bank',
            self::BON => 'Bon / Piutang',
        };
    }

}
