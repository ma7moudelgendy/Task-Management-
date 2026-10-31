<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTaskRule extends Model
{
    protected $fillable = [
        'task_id',
        'frequency',
        'interval',
        'starts_at',
        'ends_at',
        'next_run_at',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'interval' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'next_run_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

