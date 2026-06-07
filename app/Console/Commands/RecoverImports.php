<?php

namespace App\Console\Commands;

use App\Enums\ImportJobStatus;
use App\Models\ImportJob;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'monica:recover-imports')]
class RecoverImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:recover-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover import jobs stuck in processing status for more than 30 minutes, marking them as failed.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $stuckImports = ImportJob::where('status', ImportJobStatus::PROCESSING)
            ->where('started_at', '<=', now()->subMinutes(30))
            ->get();

        if ($stuckImports->isEmpty()) {
            $this->info('No stuck imports found.');
            return;
        }

        foreach ($stuckImports as $importJob) {
            $errors = $importJob->errors ?? [];
            $errors[] = [
                'row' => 0,
                'data' => [],
                'message' => 'Import job timed out. Stuck in processing for more than 30 minutes.'
            ];

            $importJob->update([
                'status' => ImportJobStatus::FAILED,
                'errors' => $errors,
                'completed_at' => now(),
            ]);

            $this->info("Recovered stuck import job ID: {$importJob->id}");
        }

        $this->info("Recovery complete. Found and resolved {$stuckImports->count()} stuck import job(s).");
    }
}
