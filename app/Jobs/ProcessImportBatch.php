<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Models\ContactInformationType;
use App\Enums\ImportJobStatus;
use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Domains\Contact\ManageContactInformation\Services\CreateContactInformation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $importJobId,
        protected array $rows
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if (!$importJob) {
            return;
        }

        // Fast cancellation check before start of batch
        if (in_array($importJob->status, [ImportJobStatus::CANCELLED, ImportJobStatus::FAILED])) {
            return;
        }

        // Preload email and phone types for the account
        $emailType = ContactInformationType::where('account_id', $importJob->account_id)
            ->where('type', 'email')
            ->first();

        $phoneType = ContactInformationType::where('account_id', $importJob->account_id)
            ->where('type', 'phone')
            ->first();

        $processedInBatch = 0;
        $failedInBatch = 0;
        $errorsInBatch = [];

        foreach ($this->rows as $index => $rowInfo) {
            $rowNumber = $rowInfo['row_number'];
            $mapped = $rowInfo['mapped'];
            $raw = $rowInfo['raw'];

            // Query database directly to see if cancelled in real-time (every 10 rows)
            if ($index % 10 === 0) {
                $currentStatus = DB::table('import_jobs')->where('id', $this->importJobId)->value('status');
                if (in_array($currentStatus, [ImportJobStatus::CANCELLED->value, ImportJobStatus::FAILED->value])) {
                    break;
                }
            }

            // Validation
            $rowErrors = [];
            if (empty($mapped['first_name']) && empty($mapped['last_name']) && empty($mapped['nickname'])) {
                $rowErrors[] = 'At least one name field (first name, last name, nickname, or full name) is required.';
            }

            if (!empty($mapped['email']) && !filter_var($mapped['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email address format.';
            }

            if (!empty($rowErrors)) {
                $failedInBatch++;
                $errorsInBatch[] = [
                    'row' => $rowNumber,
                    'data' => $raw,
                    'message' => implode(' ', $rowErrors),
                ];
                continue;
            }

            // Create Contact and Contact Information
            try {
                DB::transaction(function () use ($importJob, $mapped, $emailType, $phoneType) {
                    $contact = (new CreateContact)->execute([
                        'account_id' => $importJob->account_id,
                        'vault_id' => $importJob->vault_id,
                        'author_id' => $importJob->user_id,
                        'first_name' => $mapped['first_name'] ?? null,
                        'last_name' => $mapped['last_name'] ?? null,
                        'middle_name' => $mapped['middle_name'] ?? null,
                        'nickname' => $mapped['nickname'] ?? null,
                        'listed' => true,
                    ]);

                    if (!empty($mapped['email']) && $emailType) {
                        (new CreateContactInformation)->execute([
                            'account_id' => $importJob->account_id,
                            'vault_id' => $importJob->vault_id,
                            'author_id' => $importJob->user_id,
                            'contact_id' => $contact->id,
                            'contact_information_type_id' => $emailType->id,
                            'contact_information_kind' => null,
                            'data' => $mapped['email'],
                        ]);
                    }

                    if (!empty($mapped['phone']) && $phoneType) {
                        (new CreateContactInformation)->execute([
                            'account_id' => $importJob->account_id,
                            'vault_id' => $importJob->vault_id,
                            'author_id' => $importJob->user_id,
                            'contact_id' => $contact->id,
                            'contact_information_type_id' => $phoneType->id,
                            'contact_information_kind' => null,
                            'data' => $mapped['phone'],
                        ]);
                    }
                });

                $processedInBatch++;
            } catch (\Throwable $e) {
                $failedInBatch++;
                $errorsInBatch[] = [
                    'row' => $rowNumber,
                    'data' => $raw,
                    'message' => 'Processing error: ' . $e->getMessage(),
                ];
                Log::error("Import row {$rowNumber} failed: " . $e->getMessage(), [
                    'exception' => $e,
                    'row_data' => $raw
                ]);
            }
        }

        // Atomically update progress and status inside lockForUpdate transaction
        DB::transaction(function () use ($processedInBatch, $failedInBatch, $errorsInBatch) {
            $job = ImportJob::where('id', $this->importJobId)->lockForUpdate()->first();
            if (!$job) {
                return;
            }

            // Increment rows
            $job->processed_rows += $processedInBatch;
            $job->failed_rows += $failedInBatch;

            // Merge errors if any
            if (!empty($errorsInBatch)) {
                $existingErrors = $job->errors ?? [];
                $job->errors = array_merge($existingErrors, $errorsInBatch);
            }

            // Check if finished
            if ($job->status === ImportJobStatus::PROCESSING && ($job->processed_rows + $job->failed_rows >= $job->total_rows)) {
                $status = ($job->failed_rows === $job->total_rows) ? ImportJobStatus::FAILED : ImportJobStatus::COMPLETED;
                $job->status = $status;
                $job->completed_at = now();
            }

            $job->save();
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        DB::transaction(function () use ($exception) {
            $job = ImportJob::where('id', $this->importJobId)->lockForUpdate()->first();
            if ($job && in_array($job->status, [ImportJobStatus::PENDING, ImportJobStatus::PROCESSING])) {
                $job->update([
                    'status' => ImportJobStatus::FAILED,
                    'errors' => array_merge($job->errors ?? [], [[
                        'row' => 0,
                        'data' => [],
                        'message' => 'Batch job failed: ' . $exception->getMessage()
                    ]]),
                    'completed_at' => now(),
                ]);
            }
        });
    }
}
