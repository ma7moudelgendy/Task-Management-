<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'created_by',
        'name',
        'description',
        'status',
        'start_date',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get member record for a given user.
     */
    public function getMember(User $user): ?User
    {
        if ($this->relationLoaded('members')) {
            return $this->members->firstWhere('id', $user->id);
        }

        return $this->members()->where('project_members.user_id', $user->id)->first();
    }

    /**
     * Get project role for a given user.
     */
    public function userRole(User $user): ?\App\Enums\ProjectRole
    {
        $member = $this->getMember($user);
        if (! $member || ! $member->pivot) {
            return null;
        }

        $role = $member->pivot->role;
        if ($role instanceof \App\Enums\ProjectRole) {
            return $role;
        }

        return \App\Enums\ProjectRole::tryFrom((string) $role);
    }

    /**
     * Check if user is a member of the project.
     */
    public function isMember(User $user): bool
    {
        return $this->getMember($user) !== null;
    }

    /**
     * Check if user is an owner of the project.
     */
    public function isOwner(User $user): bool
    {
        return $this->userRole($user) === \App\Enums\ProjectRole::OWNER;
    }

    /**
     * Check if user is an owner or manager of the project.
     */
    public function isOwnerOrManager(User $user): bool
    {
        $role = $this->userRole($user);
        return $role === \App\Enums\ProjectRole::OWNER || $role === \App\Enums\ProjectRole::MANAGER;
    }

    /**
     * Count remaining owners in the project.
     */
    public function ownersCount(): int
    {
        return $this->members()->where('project_members.role', \App\Enums\ProjectRole::OWNER->value)->count();
    }
}

