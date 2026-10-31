<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SyncApidogSpec extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidog:sync
                            {--file= : Path to the OpenAPI JSON file}
                            {--project-id= : Apidog Project ID (defaults to APIDOG_PROJECT_ID env)}
                            {--token= : Apidog Access Token (defaults to APIDOG_ACCESS_TOKEN env)}
                            {--overwrite : Overwrite existing endpoints instead of merging}
                            {--dry-run : Preview sync details without making network requests}
                            {--force : Explicit confirmation to execute the sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize generated OpenAPI specification with Apidog project';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('../docs/openapi.json');
        if (! File::exists($filePath)) {
            $filePath = base_path('docs/openapi.json');
        }

        if (! File::exists($filePath)) {
            $this->error("OpenAPI specification file not found at: {$filePath}");
            $this->comment("Run 'php artisan openapi:generate' first.");

            return Command::FAILURE;
        }

        $specContent = File::get($filePath);
        $spec = json_decode($specContent, true);

        if (! $spec || ! isset($spec['openapi'], $spec['paths'])) {
            $this->error("Invalid OpenAPI specification content in: {$filePath}");

            return Command::FAILURE;
        }

        $projectId = $this->option('project-id') ?: env('APIDOG_PROJECT_ID');
        $token = $this->option('token') ?: env('APIDOG_ACCESS_TOKEN');
        $overwrite = $this->option('overwrite');
        $isDryRun = $this->option('dry-run') || ! $this->option('force');

        $endpointList = [];
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $op) {
                if (in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'])) {
                    $endpointList[] = [
                        'method' => strtoupper($method),
                        'path' => '/api/v1'.$path,
                        'summary' => $op['summary'] ?? ($op['operationId'] ?? 'N/A'),
                        'tags' => implode(', ', $op['tags'] ?? ['Default']),
                        'auth' => isset($op['security']) ? 'Bearer (Sanctum)' : 'Public',
                    ];
                }
            }
        }

        $this->newLine();
        $this->info('=== Apidog OpenAPI Sync Plan ===');
        $this->line('Specification File : '.realpath($filePath));
        $this->line('OpenAPI Version    : '.$spec['openapi']);
        $this->line('API Title          : '.$spec['info']['title']);
        $this->line('Total Operations   : '.count($endpointList));
        $this->line('Target Project ID  : '.($projectId ? $projectId : '[NOT SET - Set APIDOG_PROJECT_ID]'));
        $this->line('Merge Strategy     : '.($overwrite ? 'overwrite (replaces conflicting endpoints)' : 'merge (preserves unchanged settings)'));
        $this->newLine();

        $this->table(['Method', 'Endpoint', 'Summary', 'Tags', 'Authentication'], $endpointList);
        $this->newLine();

        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No changes sent to Apidog.');
            $this->line('To synchronize changes to your Apidog project:');
            $this->line('1. Provide credentials via environment variables:');
            $this->line('   - Set APIDOG_PROJECT_ID=<your-project-id>');
            $this->line('   - Set APIDOG_ACCESS_TOKEN=<your-personal-access-token>');
            $this->line('2. Run with explicit confirmation:');
            $this->line('   php artisan apidog:sync --force');
            $this->newLine();
            $this->line('Manual Import Workflow (No credentials needed):');
            $this->line('1. Open Apidog (https://apidog.com) and navigate to your project.');
            $this->line("2. Go to 'Settings' -> 'Import Data' (or click '+' -> 'Import').");
            $this->line("3. Select 'OpenAPI / Swagger' format.");
            $this->line("4. Drag and drop 'docs/openapi.json' or paste its contents.");
            $this->line('5. Select merge/overwrite options and confirm.');

            return Command::SUCCESS;
        }

        if (empty($projectId) || empty($token)) {
            $this->error('Apidog credentials missing. Both APIDOG_PROJECT_ID and APIDOG_ACCESS_TOKEN must be provided to sync.');

            return Command::FAILURE;
        }

        $apiUrl = "https://api.apidog.com/v1/projects/{$projectId}/import-openapi?locale=en-US";
        $this->info("Sending OpenAPI specification to Apidog ({$apiUrl})...");

        try {
            $payload = [
                'input' => $specContent,
                'options' => [
                    'updateEndpointsRule' => $overwrite ? 'overwrite' : 'merge',
                    'cudFoldersRule' => 'createUpdate',
                    'importAuth' => true,
                ],
            ];

            $response = Http::withHeaders([
                'X-Apidog-Api-Version' => '2024-03-28',
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($apiUrl, $payload);

            if ($response->successful()) {
                $this->info('✓ Apidog synchronization successful!');
                $resData = $response->json();
                if (isset($resData['data'])) {
                    $this->line('Import summary: '.json_encode($resData['data'], JSON_PRETTY_PRINT));
                }

                return Command::SUCCESS;
            }

            $this->error('Apidog API request failed with status: '.$response->status());
            $this->line('Response: '.$response->body());

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Failed to connect to Apidog API: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
