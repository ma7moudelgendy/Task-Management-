<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        $name = fake()->unique()->company() . ' Team';

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'created_by' => User::factory(),
        ];
    }
}
