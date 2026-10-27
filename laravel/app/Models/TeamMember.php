<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamMember extends Pivot
{
    protected $table = 'team_members';

    public $incrementing = true;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'joined_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
