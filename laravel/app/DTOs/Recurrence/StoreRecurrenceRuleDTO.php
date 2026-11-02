<?php

namespace App\DTOs\Recurrence;

use App\Enums\RecurrenceFrequency;
use App\Http\Requests\Api\Recurrence\StoreRecurrenceRuleRequest;
use Carbon\Carbon;

readonly class StoreRecurrenceRuleDTO
{
    public function __construct(
        public RecurrenceFrequency $frequency,
        public int $interval,
        public Carbon $startsAt,
        public ?Carbon $endsAt,
        public string $timezone = 'UTC',
    ) {}

    public static function fromRequest(StoreRecurrenceRuleRequest $request): static
    {
        return new static(
            frequency: RecurrenceFrequency::from((string) $request->validated('frequency')),
            interval: (int) $request->validated('interval'),
            startsAt: Carbon::parse((string) $request->validated('starts_at')),
            endsAt: $request->validated('ends_at') ? Carbon::parse((string) $request->validated('ends_at')) : null,
            timezone: (string) ($request->validated('timezone') ?? 'UTC'),
        );
    }
}
