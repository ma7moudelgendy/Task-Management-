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

    /**
     * Get the allowed target statuses from the current status.
     *
     * @return array<TaskStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::TODO => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::IN_REVIEW, self::TODO, self::CANCELLED],
            self::IN_REVIEW => [self::DONE, self::IN_PROGRESS, self::CANCELLED],
            self::DONE => [self::IN_PROGRESS],
            self::CANCELLED => [self::TODO],
        };
    }

    /**
     * Determine if transition to another status is permitted.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}

