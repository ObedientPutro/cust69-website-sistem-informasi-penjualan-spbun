<?php

namespace App\Traits;

trait EnumHelper
{
    /**
     * Mengubah Enum menjadi Array untuk opsi Select/Dropdown Frontend.
     * Format: [['id' => 'value', 'name' => 'Label']]
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value'   => $case->value,
            'label' => $case->getLabel(),
        ], self::cases());
    }

    /**
     * Mengambil hanya values-nya saja (untuk validasi Rule::in).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
