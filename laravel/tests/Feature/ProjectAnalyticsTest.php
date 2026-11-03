<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $member;
    protected User $outsider;
    protected Team $team;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Analytics Owner', 'email' => 'a_owner@example.com']);
        $this->member = User::factory()->create(['name' => 'Analytics Member', 'email' => 'a_member@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Analytics Outsider', 'email' => 'a_outsider@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->owner->id]);
        $this->team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
            'name' => 'Mobile App v2',
        ]);

        $this->project->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->member->id, ['role' => ProjectRole::MEMBER->value]);
    }

    public function test_project_completion_percentage_calculation(): void
    {
        Sanctum::actingAs($this->member);

        // 2 DONE, 1 IN_PROGRESS, 1 TODO => 2 / 4 = 50.0%
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::DONE->value,
            'priority' => TaskPriority::HIGH->value,
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::DONE->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::LOW->value,
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::URGENT->value,
        ]);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/analytics");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'project_id' => $this->project->id,
                    'total_tasks' => 4,
                    'completed_tasks' => 2,
                    'active_tasks' => 4,
                    'completion_percentage' => 50.0,
                    'priority_distribution' => [
                        'LOW' => 1,
                        'MEDIUM' => 1,
                        'HIGH' => 1,
                        'URGENT' => 1,
                    ],
                    'status_distribution' => [
                        'DONE' => 2,
                        'IN_PROGRESS' => 1,
                        'TODO' => 1,
                    ],
                ],
            ]);
    }

    public function test_cancelled_tasks_are_excluded_from_active_pool(): void
    {
        Sanctum::actingAs($this->member);

        // 2 DONE, 2 TODO, 1 CANCELLED => active = 5 - 1 = 4 => 2 / 4 = 50.0%
        Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::DONE->value]);
        Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::DONE->value]);
        Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::TODO->value]);
        Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::TODO->value]);
        Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::CANCELLED->value]);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/analytics");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'total_tasks' => 5,
                    'completed_tasks' => 2,
                    'cancelled_tasks' => 1,
                    'active_tasks' => 4,
                    'completion_percentage' => 50.0,
                ],
            ]);
    }

    public function test_empty_project_returns_zero_percentage(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/analytics");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'active_tasks' => 0,
                    'completion_percentage' => 0.0,
                ],
            ]);
    }

    public function test_assignee_workload_breakdown(): void
    {
        Sanctum::actingAs($this->owner);

        // 1 open and 2 done assigned to $this->member
        $t1 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::IN_PROGRESS->value]);
        $t1->assignees()->attach($this->member->id, ['assigned_by' => $this->owner->id, 'assigned_at' => now()]);

        $t2 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::DONE->value]);
        $t2->assignees()->attach($this->member->id, ['assigned_by' => $this->owner->id, 'assigned_at' => now()]);

        $t3 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id, 'status' => TaskStatus::DONE->value]);
        $t3->assignees()->attach($this->member->id, ['assigned_by' => $this->owner->id, 'assigned_at' => now()]);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/analytics");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $workloads = collect($response->json('data.assignee_workload'));
        $memberWorkload = $workloads->firstWhere('user_id', $this->member->id);

        $this->assertNotNull($memberWorkload);
        $this->assertSame(1, $memberWorkload['open_tasks_count']);
        $this->assertSame(2, $memberWorkload['completed_tasks_count']);
        $this->assertSame(3, $memberWorkload['total_assigned']);
    }

    public function test_non_member_cannot_view_project_analytics(): void
    {
        Sanctum::actingAs($this->outsider);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/analytics");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }
}

