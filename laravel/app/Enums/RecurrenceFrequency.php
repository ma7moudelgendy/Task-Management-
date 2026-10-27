<?php

namespace App\Enums;

enum RecurrenceFrequency: string
{
    case DAILY = 'DAILY';
    case WEEKLY = 'WEEKLY';
    case MONTHLY = 'MONTHLY';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
