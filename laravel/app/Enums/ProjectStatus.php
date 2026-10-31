<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ON_HOLD = 'ON_HOLD';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

