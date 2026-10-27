<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', 'now');
        $dueDate = fake()->dateTimeBetween('now', '+3 months');

        return [
            'team_id' => Team::factory(),
            'created_by' => User::factory(),
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => $startDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }
}
