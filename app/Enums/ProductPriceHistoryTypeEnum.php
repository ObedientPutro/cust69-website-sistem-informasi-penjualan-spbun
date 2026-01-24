<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum ProductPriceHistoryTypeEnum: string
{
    use EnumHelper;

    case MANUAL_UPDATE = 'manual_update';
    case RESTOCK_ADJUSTMENT = 'restock_adjustment';
    case OPNAME_ADJUSTMENT = 'opname_adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL_UPDATE => 'Update Manual (Master Data)',
            self::RESTOCK_ADJUSTMENT => 'Penerimaan Stok (Restock)',
            self::OPNAME_ADJUSTMENT => 'Penyesuaian Stok Opname',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MANUAL_UPDATE => 'warning',
            self::RESTOCK_ADJUSTMENT => 'success',
            self::OPNAME_ADJUSTMENT => 'info',
        };
    }
}
