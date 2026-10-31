<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'parent_task_id',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'start_at',
        'due_at',
        'completed_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'start_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')
            ->withPivot('assigned_by', 'assigned_at');
    }

    /**
     * Prerequisites that must be finished before this task.
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id');
    }

    /**
     * Tasks that are blocked waiting on this task.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function recurrenceRule(): HasOne
    {
        return $this->hasOne(RecurringTaskRule::class);
    }

    /**
     * Check if a user is an assignee of this task.
     */
    public function isAssignee(User $user): bool
    {
        if ($this->relationLoaded('assignees')) {
            return $this->assignees->contains('id', $user->id);
        }

        return $this->assignees()->where('task_assignees.user_id', $user->id)->exists();
    }

    /**
     * Check if a user is the creator of this task.
     */
    public function isCreator(User $user): bool
    {
        return (int) $this->created_by === (int) $user->id;
    }

    /**
     * Determine if a potential parent would cause a circular hierarchy.
     */
    public function wouldCauseCycle(int $newParentId): bool
    {
        if ((int) $this->id === (int) $newParentId) {
            return true;
        }

        $currentParentId = $newParentId;
        while ($currentParentId) {
            if ((int) $currentParentId === (int) $this->id) {
                return true;
            }

            $parent = static::query()->select(['id', 'parent_task_id'])->find($currentParentId);
            if (! $parent) {
                break;
            }

            $currentParentId = $parent->parent_task_id;
        }

        return false;
    }
}

