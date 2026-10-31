<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Task\DependencyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CycleDetectionTest extends TestCase
{
    use DatabaseTransactions;

    protected DependencyService $dependencyService;
    protected Project $project;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dependencyService = app(DependencyService::class);

        $this->user = User::factory()->create();
        $team = Team::factory()->create(['created_by' => $this->user->id]);
        $this->project = Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_self_dependency_is_detected_as_cycle(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);

        $this->assertTrue($this->dependencyService->wouldCreateCycle($task->id, $task->id));
    }

    public function test_direct_two_node_cycle_is_detected(): void
    {
        $taskA = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $taskB = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);

        // Task A depends on Task B
        $this->dependencyService->addDependency($taskA, $taskB->id, $this->user);

        // Attempting to make Task B depend on Task A must be detected as a cycle
        $this->assertTrue($this->dependencyService->wouldCreateCycle($taskB->id, $taskA->id));
    }

    public function test_transitive_three_node_cycle_is_detected(): void
    {
        $taskA = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $taskB = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $taskC = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);

        // A -> B -> C
        $this->dependencyService->addDependency($taskA, $taskB->id, $this->user);
        $this->dependencyService->addDependency($taskB, $taskC->id, $this->user);

        // Attempting to make Task C depend on Task A creates C -> A -> B -> C loop
        $this->assertTrue($this->dependencyService->wouldCreateCycle($taskC->id, $taskA->id));
    }

    public function test_deep_transitive_cycle_is_detected(): void
    {
        $t1 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $t2 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $t3 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $t4 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $t5 = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);

        // T1 -> T2 -> T3 -> T4 -> T5
        $this->dependencyService->addDependency($t1, $t2->id, $this->user);
        $this->dependencyService->addDependency($t2, $t3->id, $this->user);
        $this->dependencyService->addDependency($t3, $t4->id, $this->user);
        $this->dependencyService->addDependency($t4, $t5->id, $this->user);

        // T5 -> T1 must be blocked
        $this->assertTrue($this->dependencyService->wouldCreateCycle($t5->id, $t1->id));
        // T4 -> T2 must be blocked
        $this->assertTrue($this->dependencyService->wouldCreateCycle($t4->id, $t2->id));
    }

    public function test_valid_diamond_dag_is_permitted(): void
    {
        $taskA = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $taskB = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $taskC = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);
        $taskD = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->user->id]);

        // A depends on B and C, B depends on D
        $this->dependencyService->addDependency($taskA, $taskB->id, $this->user);
        $this->dependencyService->addDependency($taskA, $taskC->id, $this->user);
        $this->dependencyService->addDependency($taskB, $taskD->id, $this->user);

        // Adding C -> D creates diamond without cycle
        $this->assertFalse($this->dependencyService->wouldCreateCycle($taskC->id, $taskD->id));
    }
}

