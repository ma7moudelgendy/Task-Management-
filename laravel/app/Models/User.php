<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Scope query to only active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to admin users.
     */
    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', UserRole::ADMIN);
    }

    /**
     * Helper to check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Teams created by user.
     */
    public function createdTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by');
    }

    /**
     * Teams user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Projects created by user.
     */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Projects user is a member of.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Tasks assigned to user.
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees')
            ->withPivot('assigned_by', 'assigned_at');
    }

    /**
     * Tasks created by user.
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Comments written by user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'user_id');
    }
}
