<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
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

        return $this->members()->where('team_members.user_id', $user->id)->first();
    }

    /**
     * Get team role for a given user.
     */
    public function userRole(User $user): ?TeamRole
    {
        $member = $this->getMember($user);
        if (! $member || ! $member->pivot) {
            return null;
        }

        $role = $member->pivot->role;
        if ($role instanceof TeamRole) {
            return $role;
        }

        return TeamRole::tryFrom((string) $role);
    }

    /**
     * Check if user is a member of the team.
     */
    public function isMember(User $user): bool
    {
        return $this->getMember($user) !== null;
    }

    /**
     * Check if user is an owner of the team.
     */
    public function isOwner(User $user): bool
    {
        return $this->userRole($user) === TeamRole::OWNER;
    }

    /**
     * Check if user is an owner or manager.
     */
    public function isOwnerOrManager(User $user): bool
    {
        $role = $this->userRole($user);
        return $role === TeamRole::OWNER || $role === TeamRole::MANAGER;
    }

    /**
     * Count remaining owners in the team.
     */
    public function ownersCount(): int
    {
        return $this->members()->where('team_members.role', TeamRole::OWNER->value)->count();
    }
}

