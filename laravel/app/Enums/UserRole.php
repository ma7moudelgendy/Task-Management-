<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case USER = 'USER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

