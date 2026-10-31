<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $manager;
    protected User $member;
    protected User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Owner User', 'email' => 'owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Manager User', 'email' => 'manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Member User', 'email' => 'member@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Outsider User', 'email' => 'outsider@example.com']);
    }

    public function test_unauthenticated_user_cannot_access_teams(): void
    {
        $response = $this->getJson('/api/v1/teams');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
                'code' => 401,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_user_can_create_team_and_becomes_owner(): void
    {
        Sanctum::actingAs($this->owner);

        $payload = [
            'name' => 'Rocket Science Team',
        ];

        $response = $this->postJson('/api/v1/teams', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'data' => [
                    'name' => 'Rocket Science Team',
                    'slug' => 'rocket-science-team',
                    'created_by' => $this->owner->id,
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

        $teamId = $response->json('data.id');

        $this->assertDatabaseHas('teams', [
            'id' => $teamId,
            'name' => 'Rocket Science Team',
            'slug' => 'rocket-science-team',
            'created_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $this->owner->id,
            'role' => TeamRole::OWNER->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $this->owner->id,
            'team_id' => $teamId,
            'action' => 'created_team',
        ]);
    }

    public function test_custom_slug_is_used_when_provided(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/teams', [
            'name' => 'Mobile Core Team',
            'slug' => 'custom-mobile-slug',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'data' => [
                    'slug' => 'custom-mobile-slug',
                ],
            ]);

        $this->assertDatabaseHas('teams', [
            'slug' => 'custom-mobile-slug',
        ]);
    }

    public function test_slug_uniqueness_is_auto_handled_on_name_collision(): void
    {
        Sanctum::actingAs($this->owner);

        $res1 = $this->postJson('/api/v1/teams', ['name' => 'Alpha Pod']);
        $res1->assertStatus(200)->assertJson(['data' => ['slug' => 'alpha-pod']]);

        $res2 = $this->postJson('/api/v1/teams', ['name' => 'Alpha Pod']);
        $res2->assertStatus(200)->assertJson(['data' => ['slug' => 'alpha-pod-1']]);
    }

    public function test_non_member_cannot_view_or_update_team(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);

        Sanctum::actingAs($this->outsider);

        $viewResponse = $this->getJson("/api/v1/teams/{$team->id}");
        $viewResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
                'response_status' => [
                    'error' => true,
                ],
            ]);

        $updateResponse = $this->patchJson("/api/v1/teams/{$team->id}", ['name' => 'Hacked Name']);
        $updateResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_owner_can_update_team(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id, 'name' => 'Original Name']);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->patchJson("/api/v1/teams/{$team->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_owner_can_soft_delete_team(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/teams/{$team->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    public function test_manager_can_update_team_but_cannot_delete_it(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->manager->id, ['role' => TeamRole::MANAGER->value]);

        Sanctum::actingAs($this->manager);

        $updateResponse = $this->patchJson("/api/v1/teams/{$team->id}", ['name' => 'Manager Updated']);
        $updateResponse->assertStatus(200)
            ->assertJson(['key' => 'success']);

        $deleteResponse = $this->deleteJson("/api/v1/teams/{$team->id}");
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_manager_can_add_member_by_email(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->manager->id, ['role' => TeamRole::MANAGER->value]);

        Sanctum::actingAs($this->manager);

        $response = $this->postJson("/api/v1/teams/{$team->id}/members", [
            'email' => $this->member->email,
            'role' => TeamRole::MEMBER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'data' => [
                    'user_id' => $this->member->id,
                    'email' => $this->member->email,
                    'role' => TeamRole::MEMBER->value,
                ],
            ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->member->id,
            'role' => TeamRole::MEMBER->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $this->manager->id,
            'team_id' => $team->id,
            'action' => 'added_team_member',
        ]);
    }

    public function test_duplicate_member_addition_fails_with_validation_error(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/teams/{$team->id}/members", [
            'email' => $this->member->email,
            'role' => TeamRole::MEMBER->value,
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

    public function test_manager_cannot_change_member_roles(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->manager->id, ['role' => TeamRole::MANAGER->value]);
        $team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        Sanctum::actingAs($this->manager);

        $response = $this->patchJson("/api/v1/teams/{$team->id}/members/{$this->member->id}", [
            'role' => TeamRole::MANAGER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_owner_can_change_member_role(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->patchJson("/api/v1/teams/{$team->id}/members/{$this->member->id}", [
            'role' => TeamRole::MANAGER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->member->id,
            'role' => TeamRole::MANAGER->value,
        ]);
    }

    public function test_last_owner_cannot_be_demoted(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->patchJson("/api/v1/teams/{$team->id}/members/{$this->owner->id}", [
            'role' => TeamRole::MEMBER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->owner->id,
            'role' => TeamRole::OWNER->value,
        ]);
    }

    public function test_last_owner_cannot_be_removed(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/teams/{$team->id}/members/{$this->owner->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->owner->id,
        ]);
    }

    public function test_owner_can_remove_member(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/teams/{$team->id}/members/{$this->member->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->member->id,
        ]);
    }

    public function test_member_can_list_their_teams(): void
    {
        $team1 = Team::factory()->create(['created_by' => $this->owner->id, 'name' => 'Team Alpha']);
        $team1->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);

        $team2 = Team::factory()->create(['created_by' => $this->manager->id, 'name' => 'Team Beta']);
        $team2->members()->attach($this->manager->id, ['role' => TeamRole::OWNER->value]);
        $team2->members()->attach($this->owner->id, ['role' => TeamRole::MEMBER->value]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/teams');

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
                            'slug',
                            'created_by',
                            'member_count',
                            'helpers',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'total',
                    ],
                ],
            ]);

        $teamIds = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($teamIds->contains($team1->id));
        $this->assertTrue($teamIds->contains($team2->id));
    }

    public function test_member_can_view_team_members(): void
    {
        $team = Team::factory()->create(['created_by' => $this->owner->id]);
        $team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson("/api/v1/teams/{$team->id}/members");

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
