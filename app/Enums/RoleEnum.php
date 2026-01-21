<?php

namespace App\Enums;

enum RoleEnum : string
{
    case OWNER = 'owner';
    case OPERATOR = 'operator';

    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => 'Super Admin (Owner)',
            self::OPERATOR => 'Operator (Staff)',
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
