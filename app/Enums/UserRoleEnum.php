<?php

namespace App\Enums;

enum UserRoleEnum : string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case OPERATOR = 'operator';

    public function getLabel(): string
    {
        return match ($this) {
            self::OWNER => 'Super Admin (Owner)',
            self::ADMIN => 'Head Staff (Admin)',
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
