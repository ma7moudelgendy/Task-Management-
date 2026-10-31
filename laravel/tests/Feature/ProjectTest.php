<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
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

class ProjectTest extends TestCase
{
    use DatabaseTransactions;

    protected User $teamOwner;
    protected User $teamMember;
    protected User $outsider;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teamOwner = User::factory()->create(['name' => 'Team Owner', 'email' => 'team_owner@example.com']);
        $this->teamMember = User::factory()->create(['name' => 'Team Member', 'email' => 'team_member@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Outsider', 'email' => 'outsider@example.com']);

        $this->team = Team::factory()->create([
            'name' => 'Engineering Team',
            'created_by' => $this->teamOwner->id,
        ]);

        $this->team->members()->attach($this->teamOwner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->teamMember->id, ['role' => TeamRole::MEMBER->value]);
    }

    public function test_unauthenticated_user_cannot_access_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
                'code' => 401,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_team_owner_can_create_project_and_becomes_owner(): void
    {
        Sanctum::actingAs($this->teamOwner);

        $payload = [
            'team_id' => $this->team->id,
            'name' => 'Core Architecture Revamp',
            'description' => 'Redesigning database layer',
            'status' => 'ACTIVE',
            'start_date' => '2026-11-01',
            'due_date' => '2026-12-31',
        ];

        $response = $this->postJson('/api/v1/projects', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'data' => [
                    'team_id' => $this->team->id,
                    'name' => 'Core Architecture Revamp',
                    'status' => 'ACTIVE',
                    'created_by' => $this->teamOwner->id,
                    'member_count' => 1,
                    'helpers' => [
                        'can_update' => true,
                        'can_delete' => true,
                        'can_manage_members' => true,
                        'can_change_role' => true,
                    ],
                ],
                'response_status' => [
                    'error' => false,
                ],
            ]);

        $projectId = $response->json('data.id');

        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'name' => 'Core Architecture Revamp',
            'created_by' => $this->teamOwner->id,
        ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $projectId,
            'user_id' => $this->teamOwner->id,
            'role' => ProjectRole::OWNER->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $this->teamOwner->id,
            'project_id' => $projectId,
            'action' => 'created_project',
        ]);
    }

    public function test_regular_team_member_cannot_create_project(): void
    {
        Sanctum::actingAs($this->teamMember);

        $response = $this->postJson('/api/v1/projects', [
            'team_id' => $this->team->id,
            'name' => 'Unauthorized Project',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_date_validation_enforces_chronological_order(): void
    {
        Sanctum::actingAs($this->teamOwner);

        $response = $this->postJson('/api/v1/projects', [
            'team_id' => $this->team->id,
            'name' => 'Invalid Dates Project',
            'start_date' => '2026-12-15',
            'due_date' => '2026-11-01',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_membership_separation_team_member_cannot_view_unjoined_project(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
            'name' => 'Top Secret Project',
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        // TeamMember is in the team, but NOT in project_members
        Sanctum::actingAs($this->teamMember);

        $viewResponse = $this->getJson("/api/v1/projects/{$project->id}");
        $viewResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);

        $updateResponse = $this->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Infiltrated Name',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_cannot_add_non_team_member_to_project(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        Sanctum::actingAs($this->teamOwner);

        // Outsider is not a member of $this->team
        $response = $this->postJson("/api/v1/projects/{$project->id}/members", [
            'user_id' => $this->outsider->id,
            'role' => ProjectRole::MEMBER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_can_add_team_member_to_project(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        Sanctum::actingAs($this->teamOwner);

        $response = $this->postJson("/api/v1/projects/{$project->id}/members", [
            'user_id' => $this->teamMember->id,
            'role' => ProjectRole::MANAGER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'data' => [
                    'user_id' => $this->teamMember->id,
                    'role' => ProjectRole::MANAGER->value,
                ],
            ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->teamMember->id,
            'role' => ProjectRole::MANAGER->value,
        ]);
    }

    public function test_duplicate_project_member_fails_validation(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);
        $project->members()->attach($this->teamMember->id, ['role' => ProjectRole::MEMBER->value]);

        Sanctum::actingAs($this->teamOwner);

        $response = $this->postJson("/api/v1/projects/{$project->id}/members", [
            'user_id' => $this->teamMember->id,
            'role' => ProjectRole::VIEWER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);
    }

    public function test_project_manager_can_update_project_but_cannot_delete(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
            'name' => 'Original Project Name',
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);
        $project->members()->attach($this->teamMember->id, ['role' => ProjectRole::MANAGER->value]);

        Sanctum::actingAs($this->teamMember);

        $updateResponse = $this->patchJson("/api/v1/projects/{$project->id}", [
            'name' => 'Manager Updated Name',
            'status' => 'ON_HOLD',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'name' => 'Manager Updated Name',
                    'status' => 'ON_HOLD',
                ],
            ]);

        $deleteResponse = $this->deleteJson("/api/v1/projects/{$project->id}");
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_project_owner_can_soft_delete_project(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        Sanctum::actingAs($this->teamOwner);

        $response = $this->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_project_owner_can_change_member_role(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);
        $project->members()->attach($this->teamMember->id, ['role' => ProjectRole::MEMBER->value]);

        Sanctum::actingAs($this->teamOwner);

        $response = $this->patchJson("/api/v1/projects/{$project->id}/members/{$this->teamMember->id}", [
            'role' => ProjectRole::VIEWER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->teamMember->id,
            'role' => ProjectRole::VIEWER->value,
        ]);
    }

    public function test_last_owner_cannot_be_demoted(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        Sanctum::actingAs($this->teamOwner);

        $response = $this->patchJson("/api/v1/projects/{$project->id}/members/{$this->teamOwner->id}", [
            'role' => ProjectRole::MEMBER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->teamOwner->id,
            'role' => ProjectRole::OWNER->value,
        ]);
    }

    public function test_last_owner_cannot_be_removed(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        Sanctum::actingAs($this->teamOwner);

        $response = $this->deleteJson("/api/v1/projects/{$project->id}/members/{$this->teamOwner->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->teamOwner->id,
        ]);
    }

    public function test_removing_member_revokes_access_while_preserving_task_history(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);
        $project->members()->attach($this->teamMember->id, ['role' => ProjectRole::MEMBER->value]);

        // Create a task assigned to teamMember
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $this->teamOwner->id,
            'title' => 'Historical Task',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);
        $task->assignees()->attach($this->teamMember->id, ['assigned_by' => $this->teamOwner->id]);

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $this->teamMember->id,
        ]);

        // Remove teamMember from project
        Sanctum::actingAs($this->teamOwner);
        $removeResponse = $this->deleteJson("/api/v1/projects/{$project->id}/members/{$this->teamMember->id}");
        $removeResponse->assertStatus(200)->assertJson(['key' => 'success']);

        // Assert member detached from project
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->teamMember->id,
        ]);

        // Assert task assignment is strictly preserved for audit/history
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $this->teamMember->id,
        ]);

        // Assert project access is immediately revoked
        Sanctum::actingAs($this->teamMember);
        $accessResponse = $this->getJson("/api/v1/projects/{$project->id}");
        $accessResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_member_can_list_their_projects_and_filter_by_team_and_status(): void
    {
        $project1 = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
            'status' => ProjectStatus::ACTIVE->value,
        ]);
        $project1->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        $project2 = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
            'status' => ProjectStatus::ON_HOLD->value,
        ]);
        $project2->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);

        Sanctum::actingAs($this->teamOwner);

        // Filter by status ON_HOLD
        $response = $this->getJson("/api/v1/projects?status=ON_HOLD");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'status',
                            'member_count',
                            'tasks_count',
                            'helpers',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'total',
                    ],
                ],
            ]);

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertFalse($ids->contains($project1->id));
        $this->assertTrue($ids->contains($project2->id));
    }

    public function test_enrolled_member_can_view_project_members(): void
    {
        $project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->teamOwner->id,
        ]);
        $project->members()->attach($this->teamOwner->id, ['role' => ProjectRole::OWNER->value]);
        $project->members()->attach($this->teamMember->id, ['role' => ProjectRole::MEMBER->value]);

        Sanctum::actingAs($this->teamMember);

        $response = $this->getJson("/api/v1/projects/{$project->id}/members");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'name',
                        'email',
                        'role',
                        'joined_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }
}

