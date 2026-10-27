<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use App\Models\TaskStatusHistory;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Admin User
            $admin = User::firstOrCreate(
                ['email' => 'admin@taskflow.local'],
                [
                    'name' => 'System Admin',
                    'password' => Hash::make('Password123!'),
                    'role' => UserRole::ADMIN,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // 2. Regular Users
            $users = collect();
            for ($i = 1; $i <= 5; $i++) {
                $user = User::firstOrCreate(
                    ['email' => "user{$i}@taskflow.local"],
                    [
                        'name' => "User {$i}",
                        'password' => Hash::make('Password123!'),
                        'role' => UserRole::USER,
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                );
                $users->push($user);
            }

            // 3. Two Teams
            $team1 = Team::firstOrCreate(
                ['slug' => 'engineering-team'],
                [
                    'name' => 'Engineering Team',
                    'created_by' => $users[0]->id,
                ]
            );

            $team2 = Team::firstOrCreate(
                ['slug' => 'marketing-team'],
                [
                    'name' => 'Marketing Team',
                    'created_by' => $users[1]->id,
                ]
            );

            // 4. Team Memberships
            TeamMember::firstOrCreate(['team_id' => $team1->id, 'user_id' => $users[0]->id], ['role' => TeamRole::OWNER]);
            TeamMember::firstOrCreate(['team_id' => $team1->id, 'user_id' => $users[2]->id], ['role' => TeamRole::MANAGER]);
            TeamMember::firstOrCreate(['team_id' => $team1->id, 'user_id' => $users[3]->id], ['role' => TeamRole::MEMBER]);

            TeamMember::firstOrCreate(['team_id' => $team2->id, 'user_id' => $users[1]->id], ['role' => TeamRole::OWNER]);
            TeamMember::firstOrCreate(['team_id' => $team2->id, 'user_id' => $users[3]->id], ['role' => TeamRole::MANAGER]);
            TeamMember::firstOrCreate(['team_id' => $team2->id, 'user_id' => $users[4]->id], ['role' => TeamRole::MEMBER]);

            // 5. 4 Projects across teams
            $proj1 = Project::firstOrCreate(
                ['team_id' => $team1->id, 'name' => 'TaskFlow Core API'],
                [
                    'created_by' => $users[0]->id,
                    'description' => 'Build scalable backend REST API with Laravel 12.',
                    'status' => ProjectStatus::ACTIVE,
                    'start_date' => now()->toDateString(),
                    'due_date' => now()->addMonths(2)->toDateString(),
                ]
            );

            $proj2 = Project::firstOrCreate(
                ['team_id' => $team1->id, 'name' => 'Infrastructure Setup'],
                [
                    'created_by' => $users[2]->id,
                    'description' => 'Docker, CI/CD, MySQL, and deployment automation.',
                    'status' => ProjectStatus::COMPLETED,
                    'start_date' => now()->subMonth()->toDateString(),
                    'due_date' => now()->subDay()->toDateString(),
                ]
            );

            $proj3 = Project::firstOrCreate(
                ['team_id' => $team2->id, 'name' => 'Product Launch Campaign'],
                [
                    'created_by' => $users[1]->id,
                    'description' => 'Q4 Marketing, outreach, and user acquisition.',
                    'status' => ProjectStatus::ACTIVE,
                    'start_date' => now()->toDateString(),
                    'due_date' => now()->addMonth()->toDateString(),
                ]
            );

            $proj4 = Project::firstOrCreate(
                ['team_id' => $team2->id, 'name' => 'Rebranding Assets'],
                [
                    'created_by' => $users[1]->id,
                    'description' => 'Brand guide, logos, social media banners.',
                    'status' => ProjectStatus::ON_HOLD,
                    'start_date' => now()->subWeeks(2)->toDateString(),
                    'due_date' => now()->addWeeks(3)->toDateString(),
                ]
            );

            // Project memberships
            ProjectMember::firstOrCreate(['project_id' => $proj1->id, 'user_id' => $users[0]->id], ['role' => ProjectRole::OWNER]);
            ProjectMember::firstOrCreate(['project_id' => $proj1->id, 'user_id' => $users[2]->id], ['role' => ProjectRole::MANAGER]);
            ProjectMember::firstOrCreate(['project_id' => $proj1->id, 'user_id' => $users[3]->id], ['role' => ProjectRole::MEMBER]);

            ProjectMember::firstOrCreate(['project_id' => $proj3->id, 'user_id' => $users[1]->id], ['role' => ProjectRole::OWNER]);
            ProjectMember::firstOrCreate(['project_id' => $proj3->id, 'user_id' => $users[4]->id], ['role' => ProjectRole::MEMBER]);

            // 6. 20 Tasks
            $allTasks = [];
            for ($t = 1; $t <= 20; $t++) {
                $targetProj = ($t <= 10) ? $proj1 : (($t <= 15) ? $proj2 : $proj3);
                $creator = ($t % 2 === 0) ? $users[0] : $users[1];

                $parentTaskId = null;
                if ($t > 3 && $t % 4 === 0 && isset($allTasks[$t - 2])) {
                    $parentTaskId = $allTasks[$t - 2]->id;
                }

                $task = Task::firstOrCreate(
                    ['project_id' => $targetProj->id, 'title' => "Task {$t}: " . ucfirst(fake()->words(3, true))],
                    [
                        'parent_task_id' => $parentTaskId,
                        'created_by' => $creator->id,
                        'description' => fake()->paragraph(),
                        'status' => ($t % 3 === 0) ? TaskStatus::DONE : (($t % 2 === 0) ? TaskStatus::IN_PROGRESS : TaskStatus::TODO),
                        'priority' => ($t % 4 === 0) ? TaskPriority::URGENT : TaskPriority::MEDIUM,
                        'start_at' => now(),
                        'due_at' => now()->addDays($t),
                        'completed_at' => ($t % 3 === 0) ? now() : null,
                        'position' => $t,
                    ]
                );

                $allTasks[$t] = $task;

                // Assignee
                $assignee = ($t % 2 === 0) ? $users[2] : $users[3];
                TaskAssignee::firstOrCreate(
                    ['task_id' => $task->id, 'user_id' => $assignee->id],
                    ['assigned_by' => $creator->id, 'assigned_at' => now()]
                );

                // Comments
                if ($t <= 5) {
                    TaskComment::create([
                        'task_id' => $task->id,
                        'user_id' => $assignee->id,
                        'body' => "Work in progress for task #{$t}.",
                    ]);
                }

                // Status history
                TaskStatusHistory::create([
                    'task_id' => $task->id,
                    'changed_by' => $creator->id,
                    'old_status' => TaskStatus::TODO->value,
                    'new_status' => $task->status->value,
                    'reason' => 'Initial seeder assignment',
                    'created_at' => now(),
                ]);
            }

            // Task Dependencies (e.g. task 5 depends on task 1)
            if (isset($allTasks[5], $allTasks[1])) {
                TaskDependency::firstOrCreate([
                    'task_id' => $allTasks[5]->id,
                    'depends_on_task_id' => $allTasks[1]->id,
                ]);
            }
            if (isset($allTasks[6], $allTasks[2])) {
                TaskDependency::firstOrCreate([
                    'task_id' => $allTasks[6]->id,
                    'depends_on_task_id' => $allTasks[2]->id,
                ]);
            }
        });
    }
}
