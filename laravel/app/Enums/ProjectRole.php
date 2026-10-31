<?php

namespace App\Enums;

enum ProjectRole: string
{
    case OWNER = 'OWNER';
    case MANAGER = 'MANAGER';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

