<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $status = fake()->randomElement(TaskStatus::cases());
        $completedAt = ($status === TaskStatus::DONE) ? now() : null;

        return [
            'project_id' => Project::factory(),
            'parent_task_id' => null,
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => $status,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'start_at' => now(),
            'due_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'completed_at' => $completedAt,
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}

