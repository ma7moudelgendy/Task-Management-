<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $manager;
    protected User $member;
    protected User $viewer;
    protected User $outsider;
    protected Team $team;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Comment Owner', 'email' => 'c_owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Comment Manager', 'email' => 'c_manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Comment Member', 'email' => 'c_member@example.com']);
        $this->viewer = User::factory()->create(['name' => 'Comment Viewer', 'email' => 'c_viewer@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Comment Outsider', 'email' => 'c_outsider@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->owner->id]);
        $this->team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->manager->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->viewer->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
        ]);

        $this->project->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->manager->id, ['role' => ProjectRole::MANAGER->value]);
        $this->project->members()->attach($this->member->id, ['role' => ProjectRole::MEMBER->value]);
        $this->project->members()->attach($this->viewer->id, ['role' => ProjectRole::VIEWER->value]);

        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);
    }

    public function test_member_can_list_task_comments(): void
    {
        Sanctum::actingAs($this->member);

        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'First test comment',
        ]);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/comments");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'response_status' => [
                    'error' => false,
                ],
            ])
            ->assertJsonStructure([
                'key',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'task_id',
                            'user' => ['id', 'name', 'email'],
                            'body',
                            'created_at',
                            'updated_at',
                            'helpers' => [
                                'can_update',
                                'can_delete',
                            ],
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);
    }

    public function test_outsider_cannot_list_task_comments(): void
    {
        Sanctum::actingAs($this->outsider);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/comments");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_member_can_create_comment(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/comments", [
            'body' => 'This is a helpful task comment.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'body' => 'This is a helpful task comment.',
                    'user' => [
                        'id' => $this->member->id,
                    ],
                    'helpers' => [
                        'can_update' => true,
                        'can_delete' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'This is a helpful task comment.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $this->task->id,
            'action' => 'added_comment',
        ]);
    }

    public function test_viewer_cannot_create_comment(): void
    {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/comments", [
            'body' => 'Viewer trying to comment',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_outsider_cannot_create_comment(): void
    {
        Sanctum::actingAs($this->outsider);

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/comments", [
            'body' => 'Outsider trying to comment',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_comment_content_validation_required(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/comments", [
            'body' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_author_can_update_own_comment(): void
    {
        Sanctum::actingAs($this->member);

        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'Original comment',
        ]);

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", [
            'body' => 'Updated comment content',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'id' => $comment->id,
                    'body' => 'Updated comment content',
                ],
            ]);

        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'body' => 'Updated comment content',
        ]);
    }

    public function test_non_author_cannot_update_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'Member original comment',
        ]);

        // Even manager cannot edit another member's comment content
        Sanctum::actingAs($this->manager);

        $response = $this->patchJson("/api/v1/comments/{$comment->id}", [
            'body' => 'Manager trying to edit comment',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_author_can_delete_own_comment(): void
    {
        Sanctum::actingAs($this->member);

        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'To be deleted',
        ]);

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $this->task->id,
            'action' => 'deleted_comment',
        ]);
    }

    public function test_project_manager_can_delete_comment_moderation(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'Offensive comment to moderate',
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
    }

    public function test_other_member_cannot_delete_comment(): void
    {
        $otherMember = User::factory()->create(['name' => 'Other Member', 'email' => 'other_m@example.com']);
        $this->project->members()->attach($otherMember->id, ['role' => ProjectRole::MEMBER->value]);

        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body' => 'My comment',
        ]);

        Sanctum::actingAs($otherMember);

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }
}
