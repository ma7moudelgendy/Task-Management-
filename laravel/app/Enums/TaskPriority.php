<?php

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case URGENT = 'URGENT';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
