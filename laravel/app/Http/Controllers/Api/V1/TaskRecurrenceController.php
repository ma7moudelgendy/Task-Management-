<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Recurrence\StoreRecurrenceRuleDTO;
use App\Http\Requests\Api\Recurrence\StoreRecurrenceRuleRequest;
use App\Http\Resources\Api\RecurringTaskRuleResource;
use App\Models\Task;
use App\Services\RecurrenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class TaskRecurrenceController extends ApiController
{
    public function __construct(
        protected RecurrenceService $recurrenceService,
    ) {}

    /**
     * Get recurrence rule for a task.
     */
    public function show(Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($task) {
            $this->authorize('view', $task);

            $rule = $task->recurrenceRule;
            if (! $rule) {
                throw new ModelNotFoundException('Recurrence rule not found for this task.');
            }

            return $this->jsonResponse(
                data: RecurringTaskRuleResource::make($rule),
                msg: 'Recurrence rule retrieved successfully.'
            );
        });
    }

    /**
     * Set or update recurrence rule for a task.
     */
    public function store(StoreRecurrenceRuleRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('update', $task);

            $dto = StoreRecurrenceRuleDTO::fromRequest($request);
            $rule = $this->recurrenceService->createOrUpdateRule($task, $dto, $request->user());

            return $this->jsonResponse(
                data: RecurringTaskRuleResource::make($rule),
                msg: 'Recurrence rule saved successfully.',
                code: 201
            );
        });
    }
}
