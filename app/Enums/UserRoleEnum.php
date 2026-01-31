<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum UserRoleEnum : string
{
    use EnumHelper;

    case OWNER = 'owner';
    case OPERATOR = 'operator';

    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => 'Owner (Pemilik)',
            self::OPERATOR => 'Operator Lapangan',
        };
    }

}
