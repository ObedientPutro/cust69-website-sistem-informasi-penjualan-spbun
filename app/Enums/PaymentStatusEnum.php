<?php

namespace App\Enums;

enum PaymentStatusEnum : string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID => 'Lunas',
            self::UNPAID => 'Belum Lunas',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'id' => $case->value,
            'name' => $case->getLabel(),
        ], self::cases());
    }
}
