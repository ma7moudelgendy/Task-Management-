<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $otherUser;
    protected Team $team;
    protected Project $project;
    protected Project $unrelatedProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Dashboard User', 'email' => 'dash_user@example.com']);
        $this->otherUser = User::factory()->create(['name' => 'Other User', 'email' => 'other_dash@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->user->id]);
        $this->team->members()->attach($this->user->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->otherUser->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
            'name' => 'Core Development',
        ]);
        $this->project->members()->attach($this->user->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->otherUser->id, ['role' => ProjectRole::MEMBER->value]);

        // Unrelated project that $this->user does NOT belong to
        $this->unrelatedProject = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->otherUser->id,
            'name' => 'Secret Project',
        ]);
        $this->unrelatedProject->members()->attach($this->otherUser->id, ['role' => ProjectRole::OWNER->value]);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
            ]);
    }

    public function test_user_dashboard_returns_correct_task_counts_by_status(): void
    {
        Sanctum::actingAs($this->user);

        // 2 TODO tasks
        for ($i = 0; $i < 2; $i++) {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
                'created_by' => $this->user->id,
                'status' => TaskStatus::TODO->value,
            ]);
            $task->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);
        }

        // 1 IN_PROGRESS task
        $taskProg = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);
        $taskProg->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);

        // 3 DONE tasks
        for ($i = 0; $i < 3; $i++) {
            $taskDone = Task::factory()->create([
                'project_id' => $this->project->id,
                'created_by' => $this->user->id,
                'status' => TaskStatus::DONE->value,
            ]);
            $taskDone->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);
        }

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'stats' => [
                        'status' => [
                            'TODO' => 2,
                            'IN_PROGRESS' => 1,
                            'DONE' => 3,
                        ],
                        'total_assigned' => 6,
                    ],
                ],
            ]);
    }

    public function test_tasks_assigned_to_others_are_excluded_from_user_dashboard(): void
    {
        Sanctum::actingAs($this->user);

        // Task assigned only to $this->otherUser
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'status' => TaskStatus::TODO->value,
        ]);
        $task->assignees()->attach($this->otherUser->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'stats' => [
                        'status' => [
                            'TODO' => 0,
                            'IN_PROGRESS' => 0,
                            'DONE' => 0,
                        ],
                        'total_assigned' => 0,
                    ],
                ],
            ]);
    }

    public function test_overdue_tasks_only_include_past_due_incomplete_tasks(): void
    {
        Sanctum::actingAs($this->user);

        // Overdue incomplete task
        $overdueTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'title' => 'Critical Overdue Bug',
            'status' => TaskStatus::IN_PROGRESS->value,
            'due_at' => now()->subDays(2),
        ]);
        $overdueTask->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);

        // Overdue DONE task (should NOT be in overdue list)
        $completedTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'title' => 'Completed in past',
            'status' => TaskStatus::DONE->value,
            'due_at' => now()->subDays(3),
        ]);
        $completedTask->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'stats' => [
                        'overdue_count' => 1,
                    ],
                ],
            ]);

        $overdueList = $response->json('data.overdue_tasks');
        $this->assertCount(1, $overdueList);
        $this->assertSame($overdueTask->id, $overdueList[0]['id']);
        $this->assertSame('Critical Overdue Bug', $overdueList[0]['title']);
    }

    public function test_upcoming_tasks_window(): void
    {
        Sanctum::actingAs($this->user);

        // Upcoming within 7 days
        $upcomingTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'title' => 'Sprint Review Prep',
            'status' => TaskStatus::TODO->value,
            'due_at' => now()->addDays(3),
        ]);
        $upcomingTask->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);

        // Far future (> 7 days)
        $farFutureTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'title' => 'Quarterly Planning',
            'status' => TaskStatus::TODO->value,
            'due_at' => now()->addDays(14),
        ]);
        $farFutureTask->assignees()->attach($this->user->id, ['assigned_by' => $this->user->id, 'assigned_at' => now()]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $upcomingList = $response->json('data.upcoming_tasks');

        $this->assertCount(1, $upcomingList);
        $this->assertSame($upcomingTask->id, $upcomingList[0]['id']);
        $this->assertSame('Sprint Review Prep', $upcomingList[0]['title']);
    }

    public function test_recent_activity_scoped_to_user_projects(): void
    {
        Sanctum::actingAs($this->user);

        // Activity log in user's project
        ActivityLog::create([
            'actor_id' => $this->otherUser->id,
            'team_id' => $this->team->id,
            'project_id' => $this->project->id,
            'subject_type' => Task::class,
            'subject_id' => 1,
            'action' => 'status_changed',
            'metadata' => ['new_status' => 'DONE'],
            'created_at' => now(),
        ]);

        // Activity log in unrelated project
        ActivityLog::create([
            'actor_id' => $this->otherUser->id,
            'team_id' => $this->team->id,
            'project_id' => $this->unrelatedProject->id,
            'subject_type' => Task::class,
            'subject_id' => 2,
            'action' => 'status_changed',
            'metadata' => ['new_status' => 'DONE'],
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(200);
        $activities = $response->json('data.recent_activity');

        $this->assertCount(1, $activities);
        $this->assertSame($this->project->id, $activities[0]['project']['id']);
    }
}
