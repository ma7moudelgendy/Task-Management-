<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case IN_REVIEW = 'IN_REVIEW';
    case DONE = 'DONE';
    case CANCELLED = 'CANCELLED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
