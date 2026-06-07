<?php

namespace App\Jobs;

use App\Enums\ImportJobStatus;
use App\Models\ContactInformationType;
use App\Models\ImportError;
use App\Models\ImportJob;
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

    public function __construct(
        protected int $importJobId,
        protected array $rows
    ) {
    }

    public function handle(): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if (! $importJob) {
            return;
        }

        $this->touchHeartbeat($importJob);

        if (in_array($importJob->status, [ImportJobStatus::CANCELLED, ImportJobStatus::FAILED], true)) {
            return;
        }

        if ($importJob->status === ImportJobStatus::CANCELLING) {
            $this->finalizeCancellation($importJob);
            return;
        }

        $emailType = ContactInformationType::where('account_id', $importJob->account_id)
            ->where('type', 'email')
            ->first();

        $phoneType = ContactInformationType::where('account_id', $importJob->account_id)
            ->where('type', 'phone')
            ->first();

        $processedInBatch = 0;
        $failedInBatch = 0;
        $shouldCancel = false;

        foreach ($this->rows as $index => $rowInfo) {
            $rowNumber = $rowInfo['row_number'];
            $mapped = $rowInfo['mapped'];
            $raw = $rowInfo['raw'];

            if ($index % 10 === 0) {
                $currentStatus = DB::table('import_jobs')
                    ->where('id', $this->importJobId)
                    ->value('status');

                if (in_array($currentStatus, [ImportJobStatus::CANCELLING->value, ImportJobStatus::CANCELLED->value, ImportJobStatus::FAILED->value], true)) {
                    $shouldCancel = in_array($currentStatus, [ImportJobStatus::CANCELLING->value, ImportJobStatus::CANCELLED->value], true);
                    break;
                }
            }

            $rowErrors = [];
            if (empty($mapped['first_name']) && empty($mapped['last_name']) && empty($mapped['nickname'])) {
                $rowErrors[] = 'At least one name field (first name, last name, nickname, or full name) is required.';
            }

            if (! empty($mapped['email']) && ! filter_var($mapped['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email address format.';
            }

            if (! empty($rowErrors)) {
                $failedInBatch++;
                $this->storeImportError($rowNumber, $raw, implode(' ', $rowErrors));
                continue;
            }

            try {
                DB::transaction(function () use ($importJob, $mapped, $emailType, $phoneType): void {
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

                    if (! empty($mapped['email']) && $emailType) {
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

                    if (! empty($mapped['phone']) && $phoneType) {
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
                $this->storeImportError($rowNumber, $raw, 'Processing error: ' . $e->getMessage());
                Log::error("Import row {$rowNumber} failed: " . $e->getMessage(), [
                    'exception' => $e,
                    'row_data' => $raw,
                ]);
            }
        }

        DB::transaction(function () use ($processedInBatch, $failedInBatch, $shouldCancel): void {
            $job = ImportJob::where('id', $this->importJobId)->lockForUpdate()->first();
            if (! $job) {
                return;
            }

            $job->processed_rows += $processedInBatch;
            $job->failed_rows += $failedInBatch;
            $job->last_heartbeat_at = now();

            if ($shouldCancel || $job->status === ImportJobStatus::CANCELLING) {
                $job->status = ImportJobStatus::CANCELLED;
                $job->cancelled_at = $job->cancelled_at ?? now();
                $job->completed_at = $job->completed_at ?? now();
            } elseif ($job->status === ImportJobStatus::PROCESSING && ($job->processed_rows + $job->failed_rows >= $job->total_rows)) {
                $job->status = $job->failed_rows === $job->total_rows
                    ? ImportJobStatus::FAILED
                    : ImportJobStatus::COMPLETED;
                $job->completed_at = now();
            }

            $job->save();
        });
    }

    public function failed(\Throwable $exception): void
    {
        DB::transaction(function () use ($exception): void {
            $job = ImportJob::where('id', $this->importJobId)->lockForUpdate()->first();
            if (! $job) {
                return;
            }

            if ($job->status === ImportJobStatus::CANCELLING) {
                $job->status = ImportJobStatus::CANCELLED;
                $job->cancelled_at = $job->cancelled_at ?? now();
                $job->completed_at = $job->completed_at ?? now();
                $job->last_heartbeat_at = now();
                $job->save();

                return;
            }

            if (! in_array($job->status, [ImportJobStatus::PENDING, ImportJobStatus::PROCESSING], true)) {
                return;
            }

            ImportError::create([
                'import_job_id' => $job->id,
                'row_number' => 0,
                'row_data' => null,
                'error_message' => 'Batch job failed: ' . $exception->getMessage(),
            ]);

            $job->status = ImportJobStatus::FAILED;
            $job->completed_at = now();
            $job->last_heartbeat_at = now();
            $job->save();
        });
    }

    private function touchHeartbeat(ImportJob $importJob): void
    {
        $importJob->update([
            'last_heartbeat_at' => now(),
        ]);
    }

    private function storeImportError(int $rowNumber, array $rowData, string $message): void
    {
        ImportError::create([
            'import_job_id' => $this->importJobId,
            'row_number' => $rowNumber,
            'row_data' => $rowData,
            'error_message' => $message,
        ]);
    }

    private function finalizeCancellation(ImportJob $importJob): void
    {
        DB::transaction(function () use ($importJob): void {
            $job = ImportJob::where('id', $importJob->id)->lockForUpdate()->first();
            if (! $job || $job->status !== ImportJobStatus::CANCELLING) {
                return;
            }

            $job->status = ImportJobStatus::CANCELLED;
            $job->cancelled_at = $job->cancelled_at ?? now();
            $job->completed_at = $job->completed_at ?? now();
            $job->last_heartbeat_at = now();
            $job->save();
        });
    }
}
