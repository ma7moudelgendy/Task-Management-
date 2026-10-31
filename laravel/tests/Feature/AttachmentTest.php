<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttachmentTest extends TestCase
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

        Storage::fake('local');

        $this->owner = User::factory()->create(['name' => 'Attachment Owner', 'email' => 'att_owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Attachment Manager', 'email' => 'att_manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Attachment Member', 'email' => 'att_member@example.com']);
        $this->viewer = User::factory()->create(['name' => 'Attachment Viewer', 'email' => 'att_viewer@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Attachment Outsider', 'email' => 'att_outsider@example.com']);

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

    public function test_member_can_list_attachments(): void
    {
        Sanctum::actingAs($this->member);

        TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $this->member->id,
            'disk' => 'local',
            'path' => 'attachments/test.pdf',
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/attachments");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ])
            ->assertJsonStructure([
                'key',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'task_id',
                            'uploaded_by',
                            'original_name',
                            'mime_type',
                            'size',
                            'created_at',
                            'helpers' => [
                                'can_delete',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonMissingPath('data.data.0.path')
            ->assertJsonMissingPath('data.data.0.disk');
    }

    public function test_outsider_cannot_list_attachments(): void
    {
        Sanctum::actingAs($this->outsider);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/attachments");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_member_can_upload_valid_attachment(): void
    {
        Sanctum::actingAs($this->member);

        $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'original_name' => 'report.pdf',
                    'task_id' => $this->task->id,
                    'uploaded_by' => $this->member->id,
                    'helpers' => [
                        'can_delete' => true,
                    ],
                ],
            ])
            ->assertJsonMissingPath('data.path')
            ->assertJsonMissingPath('data.disk');

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $this->task->id,
            'uploaded_by' => $this->member->id,
            'original_name' => 'report.pdf',
            'disk' => 'local',
        ]);

        $attachment = TaskAttachment::where('task_id', $this->task->id)->first();
        $this->assertNotNull($attachment);
        Storage::disk('local')->assertExists($attachment->path);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $this->task->id,
            'action' => 'uploaded_attachment',
        ]);
    }

    public function test_upload_blocks_disallowed_mime_and_extension(): void
    {
        Sanctum::actingAs($this->member);

        $scriptFile = UploadedFile::fake()->create('shell.php', 50, 'application/x-php');

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
            'file' => $scriptFile,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_upload_blocks_file_exceeding_10mb(): void
    {
        Sanctum::actingAs($this->member);

        $largeFile = UploadedFile::fake()->create('huge_doc.pdf', 10241, 'application/pdf');

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
            'file' => $largeFile,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_viewer_cannot_upload_attachment(): void
    {
        Sanctum::actingAs($this->viewer);

        $file = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_authorized_member_can_download_attachment(): void
    {
        Sanctum::actingAs($this->member);

        $filePath = 'attachments/'.$this->task->id.'/sample.pdf';
        Storage::disk('local')->put($filePath, 'PDF FILE CONTENT SAMPLE');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $this->owner->id,
            'disk' => 'local',
            'path' => $filePath,
            'original_name' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => 24,
        ]);

        $response = $this->get("/api/v1/tasks/{$this->task->id}/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-disposition'), 'sample.pdf')
        );
    }

    public function test_outsider_cannot_download_attachment_idor_protection(): void
    {
        Sanctum::actingAs($this->outsider);

        $filePath = 'attachments/'.$this->task->id.'/secret.pdf';
        Storage::disk('local')->put($filePath, 'SECRET CONTENT');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $this->owner->id,
            'disk' => 'local',
            'path' => $filePath,
            'original_name' => 'secret.pdf',
            'mime_type' => 'application/pdf',
            'size' => 14,
        ]);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/attachments/{$attachment->id}/download");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_uploader_can_delete_attachment(): void
    {
        Sanctum::actingAs($this->member);

        $filePath = 'attachments/'.$this->task->id.'/to_delete.pdf';
        Storage::disk('local')->put($filePath, 'DELETE ME');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $this->member->id,
            'disk' => 'local',
            'path' => $filePath,
            'original_name' => 'to_delete.pdf',
            'mime_type' => 'application/pdf',
            'size' => 9,
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$this->task->id}/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $this->assertDatabaseMissing('task_attachments', [
            'id' => $attachment->id,
            'deleted_at' => null,
        ]);

        Storage::disk('local')->assertMissing($filePath);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Task::class,
            'subject_id' => $this->task->id,
            'action' => 'deleted_attachment',
        ]);
    }

    public function test_project_manager_can_delete_attachment(): void
    {
        Sanctum::actingAs($this->manager);

        $filePath = 'attachments/'.$this->task->id.'/mod_delete.pdf';
        Storage::disk('local')->put($filePath, 'MANAGER DELETE');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $this->member->id,
            'disk' => 'local',
            'path' => $filePath,
            'original_name' => 'mod_delete.pdf',
            'mime_type' => 'application/pdf',
            'size' => 14,
        ]);

        $response = $this->deleteJson("/api/v1/tasks/{$this->task->id}/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $this->assertDatabaseMissing('task_attachments', [
            'id' => $attachment->id,
            'deleted_at' => null,
        ]);

        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_non_owner_member_cannot_delete_other_attachment(): void
    {
        $otherMember = User::factory()->create(['name' => 'Other Member', 'email' => 'other_att@example.com']);
        $this->project->members()->attach($otherMember->id, ['role' => ProjectRole::MEMBER->value]);

        $filePath = 'attachments/'.$this->task->id.'/cannot_delete.pdf';
        Storage::disk('local')->put($filePath, 'CANNOT DELETE');

        $attachment = TaskAttachment::create([
            'task_id' => $this->task->id,
            'uploaded_by' => $this->member->id,
            'disk' => 'local',
            'path' => $filePath,
            'original_name' => 'cannot_delete.pdf',
            'mime_type' => 'application/pdf',
            'size' => 13,
        ]);

        Sanctum::actingAs($otherMember);

        $response = $this->deleteJson("/api/v1/tasks/{$this->task->id}/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);

        Storage::disk('local')->assertExists($filePath);
    }
}
