<?php

namespace App\Console\Commands;

use App\Services\RecurrenceService;
use Illuminate\Console\Command;

class ProcessRecurringTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring task rules and generate new task instances';

    /**
     * Execute the console command.
     */
    public function handle(RecurrenceService $recurrenceService): int
    {
        $this->info('Processing due recurring task rules...');

        $processed = $recurrenceService->processDueRules();

        $this->info("✓ Generated {$processed} recurring task instance(s).");

        return Command::SUCCESS;
    }
}
