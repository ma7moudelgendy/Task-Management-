<?php

namespace App\Enums;

enum TeamRole: string
{
    case OWNER = 'OWNER';
    case MANAGER = 'MANAGER';
    case MEMBER = 'MEMBER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
