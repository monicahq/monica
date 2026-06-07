<?php

namespace App\Console\Commands;

use App\Enums\ImportJobStatus;
use App\Models\ImportError;
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
        $threshold = now()->subMinutes(30);
        $stuckImports = ImportJob::where('status', ImportJobStatus::PROCESSING)
            ->where(function ($query) use ($threshold) {
                $query->whereNotNull('last_heartbeat_at')
                    ->where('last_heartbeat_at', '<', $threshold)
                    ->orWhere(function ($query) use ($threshold) {
                        $query->whereNull('last_heartbeat_at')
                            ->where('started_at', '<', $threshold);
                    });
            })
            ->get();

        if ($stuckImports->isEmpty()) {
            $this->info('No stuck imports found.');
            return;
        }

        foreach ($stuckImports as $importJob) {
            ImportError::create([
                'import_job_id' => $importJob->id,
                'row_number' => 0,
                'row_data' => null,
                'error_message' => 'Import job timed out. Stuck in processing for more than 30 minutes.',
            ]);

            $importJob->update([
                'status' => ImportJobStatus::FAILED,
                'completed_at' => now(),
                'last_heartbeat_at' => now(),
            ]);

            $this->info("Recovered stuck import job ID: {$importJob->id}");
        }

        $this->info("Recovery complete. Found and resolved {$stuckImports->count()} stuck import job(s).");
    }
}
