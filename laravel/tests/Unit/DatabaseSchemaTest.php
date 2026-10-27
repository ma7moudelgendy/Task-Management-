<?php

namespace Tests\Unit;

use App\Enums\ProjectRole;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use DatabaseTransactions;
    public function test_duplicate_team_member_fails_unique_constraint(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by' => $user->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamRole::OWNER,
        ]);

        $this->expectException(QueryException::class);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamRole::MEMBER,
        ]);
    }

    public function test_duplicate_project_member_fails_unique_constraint(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by' => $user->id]);
        $project = Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $user->id,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectRole::OWNER,
        ]);

        $this->expectException(QueryException::class);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectRole::MEMBER,
        ]);
    }

    public function test_duplicate_task_assignee_fails_unique_constraint(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by' => $user->id]);
        $project = Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $user->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        TaskAssignee::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        TaskAssignee::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
        ]);
    }

    public function test_cascade_delete_cleans_up_tasks_and_comments(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by' => $user->id]);
        $project = Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $user->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);
        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => 'Test comment for cascade delete',
        ]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('task_comments', ['id' => $comment->id]);

        // Force delete project (or delete)
        $project->forceDelete();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
    }

    public function test_model_relationships_work_correctly(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by' => $user->id]);
        $team->members()->attach($user->id, ['role' => TeamRole::OWNER->value]);

        $project = Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $user->id,
        ]);
        $project->members()->attach($user->id, ['role' => ProjectRole::OWNER->value]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($team->creator->is($user));
        $this->assertTrue($team->members->contains($user));
        $this->assertTrue($project->team->is($team));
        $this->assertTrue($project->creator->is($user));
        $this->assertTrue($project->members->contains($user));
        $this->assertTrue($task->project->is($project));
        $this->assertTrue($task->creator->is($user));
    }
}
