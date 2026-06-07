<?php

namespace App\Jobs;

use App\Enums\ImportJobStatus;
use App\Models\ImportError;
use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $importJobId
    ) {
    }

    public function handle(): void
    {
        $importJob = DB::transaction(function (): ?ImportJob {
            $importJob = ImportJob::where('id', $this->importJobId)
                ->lockForUpdate()
                ->first();

            if (! $importJob) {
                return null;
            }

            if (in_array($importJob->status, [ImportJobStatus::CANCELLED, ImportJobStatus::FAILED, ImportJobStatus::COMPLETED], true)) {
                return null;
            }

            if ($importJob->status === ImportJobStatus::CANCELLING) {
                $this->finalizeCancellation($importJob);
                return null;
            }

            $importJob->update([
                'status' => ImportJobStatus::PROCESSING,
                'started_at' => $importJob->started_at ?? now(),
                'last_heartbeat_at' => now(),
            ]);

            return $importJob->fresh();
        });

        if (! $importJob) {
            return;
        }

        $filePath = Storage::disk('local')->path($importJob->file_path);
        if (! Storage::disk('local')->exists($importJob->file_path)) {
            $this->markFailed($importJob, 'Uploaded file not found on storage disk.');
            return;
        }

        $file = fopen($filePath, 'r');
        if (! $file) {
            $this->markFailed($importJob, 'Failed to open the uploaded file.');
            return;
        }

        try {
            $headers = fgetcsv($file);
            if (! $headers) {
                $this->markFailed($importJob, 'CSV file headers are empty or invalid.');
                return;
            }

            if (str_starts_with($headers[0], "\xEF\xBB\xBF")) {
                $headers[0] = substr($headers[0], 3);
            }

            $headerMap = [];
            foreach ($headers as $index => $header) {
                $clean = strtolower(trim($header));
                if (in_array($clean, ['first_name', 'firstname', 'first name', 'given name'])) {
                    $headerMap[$index] = 'first_name';
                } elseif (in_array($clean, ['last_name', 'lastname', 'last name', 'surname', 'family name'])) {
                    $headerMap[$index] = 'last_name';
                } elseif (in_array($clean, ['middle_name', 'middlename', 'middle name'])) {
                    $headerMap[$index] = 'middle_name';
                } elseif (in_array($clean, ['nickname', 'nick name', 'nick'])) {
                    $headerMap[$index] = 'nickname';
                } elseif (in_array($clean, ['name', 'full_name', 'fullname'])) {
                    $headerMap[$index] = 'name';
                } elseif (in_array($clean, ['email', 'email_address', 'email address'])) {
                    $headerMap[$index] = 'email';
                } elseif (in_array($clean, ['phone', 'phone_number', 'phone number', 'telephone', 'mobile'])) {
                    $headerMap[$index] = 'phone';
                }
            }

            $totalRows = 0;
            while (($row = fgetcsv($file)) !== false) {
                if (array_filter($row)) {
                    $totalRows++;
                }
            }

            if ($totalRows === 0) {
                $importJob->update([
                    'status' => ImportJobStatus::COMPLETED,
                    'total_rows' => 0,
                    'completed_at' => now(),
                    'last_heartbeat_at' => now(),
                ]);

                return;
            }

            $importJob->update([
                'total_rows' => $totalRows,
                'last_heartbeat_at' => now(),
            ]);

            rewind($file);
            fgetcsv($file);

            $batchRows = [];
            $currentRowNumber = 0;
            $shouldCancel = false;

            while (($row = fgetcsv($file)) !== false) {
                if (! array_filter($row)) {
                    continue;
                }

                $currentRowNumber++;

                if ($currentRowNumber % 10 === 0) {
                    $currentStatus = DB::table('import_jobs')
                        ->where('id', $importJob->id)
                        ->value('status');

                    if (in_array($currentStatus, [ImportJobStatus::CANCELLING->value, ImportJobStatus::CANCELLED->value, ImportJobStatus::FAILED->value], true)) {
                        $shouldCancel = in_array($currentStatus, [ImportJobStatus::CANCELLING->value, ImportJobStatus::CANCELLED->value], true);
                        break;
                    }
                }

                $rowData = [];
                $rawRow = [];
                foreach ($row as $index => $value) {
                    $val = trim($value);
                    $origHeader = $headers[$index] ?? 'column_' . ($index + 1);
                    $rawRow[$origHeader] = $val;

                    if (isset($headerMap[$index])) {
                        $rowData[$headerMap[$index]] = $val;
                    }
                }

                if (isset($rowData['name']) && empty($rowData['first_name'])) {
                    $parts = explode(' ', $rowData['name'], 2);
                    $rowData['first_name'] = $parts[0];
                    if (isset($parts[1])) {
                        $rowData['last_name'] = $parts[1];
                    }
                }

                $batchRows[] = [
                    'row_number' => $currentRowNumber,
                    'mapped' => $rowData,
                    'raw' => $rawRow,
                ];

                if (count($batchRows) === 50) {
                    ProcessImportBatch::dispatch($importJob->id, $batchRows);
                    $importJob->update([
                        'last_heartbeat_at' => now(),
                    ]);
                    $batchRows = [];
                }
            }

            if ($shouldCancel) {
                $this->finalizeCancellation($importJob);
                return;
            }

            if (count($batchRows) > 0) {
                ProcessImportBatch::dispatch($importJob->id, $batchRows);
                $importJob->update([
                    'last_heartbeat_at' => now(),
                ]);
            }
        } finally {
            fclose($file);
            Storage::disk('local')->delete($importJob->file_path);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if (! $importJob) {
            return;
        }

        if ($importJob->status === ImportJobStatus::CANCELLING) {
            $this->finalizeCancellation($importJob);
            return;
        }

        if (! in_array($importJob->status, [ImportJobStatus::PENDING, ImportJobStatus::PROCESSING], true)) {
            return;
        }

        ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 0,
            'row_data' => null,
            'error_message' => 'Import processor failed: ' . $exception->getMessage(),
        ]);

        $importJob->update([
            'status' => ImportJobStatus::FAILED,
            'completed_at' => now(),
            'last_heartbeat_at' => now(),
        ]);
    }

    private function markFailed(ImportJob $importJob, string $message): void
    {
        ImportError::create([
            'import_job_id' => $importJob->id,
            'row_number' => 0,
            'row_data' => null,
            'error_message' => $message,
        ]);

        $importJob->update([
            'status' => ImportJobStatus::FAILED,
            'completed_at' => now(),
            'last_heartbeat_at' => now(),
        ]);
    }

    private function finalizeCancellation(ImportJob $importJob): void
    {
        $importJob->update([
            'status' => ImportJobStatus::CANCELLED,
            'cancelled_at' => $importJob->cancelled_at ?? now(),
            'completed_at' => $importJob->completed_at ?? now(),
            'last_heartbeat_at' => now(),
        ]);
    }
}
