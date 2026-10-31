<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskDependency extends Pivot
{
    protected $table = 'task_dependencies';

    public $incrementing = true;

    protected $fillable = [
        'task_id',
        'depends_on_task_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function prerequisiteTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }
}

