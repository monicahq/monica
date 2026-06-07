<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Enums\ImportJobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $importJobId
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

        // Mark as processing and set started_at
        $importJob->update([
            'status' => ImportJobStatus::PROCESSING,
            'started_at' => now(),
        ]);

        if (!Storage::disk('local')->exists($importJob->file_path)) {
            $importJob->update([
                'status' => ImportJobStatus::FAILED,
                'errors' => [[
                    'row' => 0,
                    'data' => [],
                    'message' => 'Uploaded file not found on storage disk.'
                ]],
                'completed_at' => now(),
            ]);
            return;
        }

        $filePath = Storage::disk('local')->path($importJob->file_path);
        $file = fopen($filePath, 'r');
        if (!$file) {
            $importJob->update([
                'status' => ImportJobStatus::FAILED,
                'errors' => [[
                    'row' => 0,
                    'data' => [],
                    'message' => 'Failed to open the uploaded file.'
                ]],
                'completed_at' => now(),
            ]);
            return;
        }

        // Read headers
        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            $importJob->update([
                'status' => ImportJobStatus::FAILED,
                'errors' => [[
                    'row' => 0,
                    'data' => [],
                    'message' => 'CSV file headers are empty or invalid.'
                ]],
                'completed_at' => now(),
            ]);
            return;
        }

        // Strip UTF-8 BOM if present
        if (str_starts_with($headers[0], "\xEF\xBB\xBF")) {
            $headers[0] = substr($headers[0], 3);
        }

        // Map headers to normalized keys
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

        // Count total rows
        $totalRows = 0;
        while (($row = fgetcsv($file)) !== false) {
            // Only count non-empty rows
            if (array_filter($row)) {
                $totalRows++;
            }
        }

        if ($totalRows === 0) {
            fclose($file);
            $importJob->update([
                'status' => ImportJobStatus::COMPLETED,
                'total_rows' => 0,
                'completed_at' => now(),
            ]);
            return;
        }

        $importJob->update([
            'total_rows' => $totalRows,
        ]);

        // Rewind to start chunking
        rewind($file);
        // skip headers
        fgetcsv($file);

        $batchRows = [];
        $currentRowNumber = 0;

        while (($row = fgetcsv($file)) !== false) {
            if (!array_filter($row)) {
                continue; // skip empty rows
            }
            $currentRowNumber++;

            // Map row data
            $rowData = [];
            $rawRow = [];
            foreach ($row as $index => $value) {
                $val = trim($value);
                $origHeader = $headers[$index] ?? "column_" . ($index + 1);
                $rawRow[$origHeader] = $val;

                if (isset($headerMap[$index])) {
                    $rowData[$headerMap[$index]] = $val;
                }
            }

            // Fallback for full name column
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
                ProcessImportBatch::dispatch($this->importJobId, $batchRows);
                $batchRows = [];
            }
        }

        if (count($batchRows) > 0) {
            ProcessImportBatch::dispatch($this->importJobId, $batchRows);
        }

        fclose($file);

        Storage::disk('local')->delete($importJob->file_path);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $importJob = ImportJob::find($this->importJobId);
        if ($importJob && in_array($importJob->status, [ImportJobStatus::PENDING, ImportJobStatus::PROCESSING])) {
            $importJob->update([
                'status' => ImportJobStatus::FAILED,
                'errors' => array_merge($importJob->errors ?? [], [[
                    'row' => 0,
                    'data' => [],
                    'message' => 'Import processor failed: ' . $exception->getMessage()
                ]]),
                'completed_at' => now(),
            ]);
        }
    }
}
