<?php

namespace App\Jobs;

use App\Domains\Contact\ManageContact\Services\CreateContact;
use App\Models\ImportError;
use App\Models\ImportJob;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The import job ID.
     */
    public string $importJobId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * Number of rows to process per chunk.
     */
    private const CHUNK_SIZE = 50;

    /**
     * Common header keywords used to detect a header row.
     */
    private const HEADER_KEYWORDS = [
        'first', 'first_name', 'firstname', 'given', 'given_name',
        'last', 'last_name', 'lastname', 'surname', 'family',
        'email', 'email_address', 'e-mail',
        'middle', 'middle_name', 'nickname', 'phone',
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(string $importJobId)
    {
        $this->importJobId = $importJobId;
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     *
     * This method processes the CSV file in chunks. On retry after a partial
     * failure, it resumes from where it left off using the persisted
     * processed_rows counter as a checkpoint.
     *
     * Retry Safety Strategy:
     * ---------------------
     * Each row is committed inside its own DB transaction. Only AFTER the
     * transaction is committed does the processed_rows counter increment
     * atomically via an UPDATE ... SET processed_rows = processed_rows + 1
     * query. If the worker crashes between the DB commit and the counter
     * increment, one data row may potentially be re-processed on retry.
     * To mitigate duplicates in that narrow window, we check for an existing
     * contact with the same first_name + last_name + vault combination
     * before creating a new one (idempotency guard).
     */
    public function handle(): void
    {
        $import = ImportJob::findOrFail($this->importJobId);

        // Don't process if already completed, cancelled, or stuck in a terminal state
        if (in_array($import->status, [ImportJob::STATUS_COMPLETED, ImportJob::STATUS_CANCELLED])) {
            return;
        }

        // Mark as processing
        if ($import->status === ImportJob::STATUS_PENDING) {
            $import->status = ImportJob::STATUS_PROCESSING;
            $import->started_at = now();
            $import->save();
        } elseif ($import->status === ImportJob::STATUS_FAILED) {
            // Retry from failed state
            $import->status = ImportJob::STATUS_PROCESSING;
            $import->failure_message = null;
            $import->save();
        }

        // Resolve the file path
        $fullPath = Storage::path($import->file_path);
        if (! file_exists($fullPath)) {
            $this->markFailed($import, 'File not found: '.$import->file_path);

            return;
        }

        try {
            // Phase 1: Count total rows and detect header (only on first attempt)
            if ($import->total_rows === 0) {
                $this->analyzeFile($fullPath, $import);
            }

            // Phase 2: Process rows in chunks, resuming from processed_rows checkpoint
            $this->processFile($fullPath, $import);

            // Phase 3: Finalize
            $this->finalizeImport($import);
        } catch (Exception $e) {
            Log::error('Import job failed', [
                'import_job_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            $this->markFailed($import, $e->getMessage());

            throw $e; // Re-throw so the queue can handle retry/backoff
        }
    }

    /**
     * Analyze the CSV file: detect header row and count total data rows.
     */
    private function analyzeFile(string $fullPath, ImportJob $import): void
    {
        $file = new \SplFileObject($fullPath);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(',');

        $total = 0;
        $header = null;
        $headerDetected = false;

        foreach ($file as $row) {
            if ($row === null || $row === [null]) {
                continue;
            }

            // Check the first non-empty row for header keywords
            if (! $headerDetected) {
                $headerDetected = true;
                if ($this->looksLikeHeader($row)) {
                    $header = $row;

                    continue; // Don't count header as a data row
                }
            }

            $total++;
        }

        $import->has_header = $header !== null;
        $import->header = $header;
        $import->total_rows = $total;
        $import->save();
    }

    /**
     * Determine if a row looks like a header based on common keywords.
     */
    private function looksLikeHeader(array $row): bool
    {
        $lower = array_map(
            fn ($c) => is_string($c) ? strtolower(trim($c)) : '',
            $row
        );

        foreach ($lower as $cell) {
            foreach (self::HEADER_KEYWORDS as $keyword) {
                if ($cell === $keyword || str_contains($cell, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Process the CSV file row by row in chunks, resuming from the checkpoint.
     */
    private function processFile(string $fullPath, ImportJob $import): void
    {
        $file = new \SplFileObject($fullPath);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(',');

        $resumeFrom = (int) $import->processed_rows;
        $dataRowIndex = 0;
        $chunk = [];

        foreach ($file as $fileLineIndex => $row) {
            if ($row === null || $row === [null]) {
                continue;
            }

            // Skip header row if present (always the first non-empty row)
            if ($import->has_header && $fileLineIndex === 0) {
                continue;
            }

            // Skip already-processed rows (resume checkpoint)
            if ($dataRowIndex < $resumeFrom) {
                $dataRowIndex++;

                continue;
            }

            // Check for cancellation between chunks
            if (! empty($chunk) && count($chunk) % self::CHUNK_SIZE === 0) {
                $import->refresh();
                if ($import->isCancelled()) {
                    return;
                }
            }

            $chunk[] = [
                'data_row_number' => $dataRowIndex + 1, // 1-based for user display
                'row' => $row,
            ];

            if (count($chunk) >= self::CHUNK_SIZE) {
                $this->processChunk($chunk, $import);
                $chunk = [];
            }

            $dataRowIndex++;
        }

        // Process remaining rows
        if (! empty($chunk)) {
            $this->processChunk($chunk, $import);
        }
    }

    /**
     * Process a chunk of rows, isolating each row's errors.
     */
    private function processChunk(array $chunk, ImportJob $import): void
    {
        foreach ($chunk as $item) {
            $rowNumber = $item['data_row_number'];
            $row = $item['row'];

            // Check for cancellation
            if ($import->isCancelled()) {
                return;
            }

            $this->processRow($row, $rowNumber, $import);
        }
    }

    /**
     * Process a single CSV row: validate, create contact, record errors.
     *
     * Each row is processed in its own transaction. The processed_rows
     * counter is incremented atomically AFTER the transaction commits.
     */
    private function processRow(array $row, int $rowNumber, ImportJob $import): void
    {
        try {
            // Map row data using header or positional fallback
            $mapped = $this->mapRowToData($row, $import->header);

            // Validate the mapped data
            $errors = $this->validateRowData($mapped);
            if (! empty($errors)) {
                $this->recordRowError($import, $rowNumber, $row, implode('; ', $errors));
                $this->incrementCounters($import, failed: true);

                return;
            }

            // Resolve vault_id
            $vaultId = $import->vault_id;
            if (empty($vaultId)) {
                $account = \App\Models\Account::find($import->account_id);
                $vault = $account?->vaults()->first();
                $vaultId = $vault?->id;
            }

            if (empty($vaultId)) {
                $this->recordRowError($import, $rowNumber, $row, 'No vault available for account');
                $this->incrementCounters($import, failed: true);

                return;
            }

            // Build CreateContact request data
            $contactData = [
                'account_id' => $import->account_id,
                'vault_id' => $vaultId,
                'author_id' => $import->user_id,
                'first_name' => $mapped['first_name'],
                'last_name' => $mapped['last_name'] ?? null,
                'listed' => true,
            ];

            // Idempotency guard: skip if a contact with same name exists in the vault
            $existingContact = \App\Models\Contact::where('vault_id', $vaultId)
                ->where('first_name', $contactData['first_name'])
                ->where('last_name', $contactData['last_name'] ?? null)
                ->whereNull('deleted_at')
                ->first();

            if ($existingContact) {
                // Already imported (likely from a previous partial run) - count as processed, not failed
                $this->incrementCounters($import, failed: false);

                return;
            }

            // Create the contact inside a transaction
            DB::beginTransaction();
            try {
                $service = app(CreateContact::class);
                $service->execute($contactData);
                DB::commit();

                $this->incrementCounters($import, failed: false);
            } catch (Exception $e) {
                DB::rollBack();

                $this->recordRowError($import, $rowNumber, $row, 'Contact creation failed: '.$e->getMessage());
                $this->incrementCounters($import, failed: true);
            }
        } catch (Exception $e) {
            $this->recordRowError($import, $rowNumber, $row, $e->getMessage());
            $this->incrementCounters($import, failed: true);
        }
    }

    /**
     * Validate a single row's mapped data.
     *
     * @return array<string> List of validation error messages.
     */
    private function validateRowData(array $mapped): array
    {
        $errors = [];

        // first_name is required
        if (empty($mapped['first_name']) || trim($mapped['first_name']) === '') {
            $errors[] = 'Missing required field: first_name';
        }

        // Email format validation (if provided)
        if (! empty($mapped['email'])) {
            $validator = Validator::make(
                ['email' => $mapped['email']],
                ['email' => 'email']
            );
            if ($validator->fails()) {
                $errors[] = 'Invalid email format: '.$mapped['email'];
            }
        }

        // first_name max length
        if (! empty($mapped['first_name']) && mb_strlen($mapped['first_name']) > 255) {
            $errors[] = 'first_name exceeds maximum length of 255 characters';
        }

        // last_name max length
        if (! empty($mapped['last_name']) && mb_strlen($mapped['last_name']) > 255) {
            $errors[] = 'last_name exceeds maximum length of 255 characters';
        }

        return $errors;
    }

    /**
     * Map a CSV row to named fields using the header or positional fallback.
     */
    private function mapRowToData(array $row, ?array $header): array
    {
        $result = [
            'first_name' => null,
            'last_name' => null,
            'email' => null,
        ];

        if (is_array($header) && count($header) > 0) {
            // Map by header column names
            foreach ($header as $i => $h) {
                $key = strtolower(trim((string) $h));
                $value = isset($row[$i]) ? trim((string) $row[$i]) : null;

                if ($value === '') {
                    $value = null;
                }

                if (in_array($key, ['first_name', 'firstname', 'first', 'given', 'given_name'])) {
                    $result['first_name'] = $value;
                } elseif (in_array($key, ['last_name', 'lastname', 'last', 'surname', 'family', 'family_name'])) {
                    $result['last_name'] = $value;
                } elseif (in_array($key, ['email', 'email_address', 'e-mail'])) {
                    $result['email'] = $value;
                } elseif (in_array($key, ['middle_name', 'middle', 'middlename'])) {
                    $result['middle_name'] = $value;
                } elseif (in_array($key, ['nickname', 'nick'])) {
                    $result['nickname'] = $value;
                }
            }
        } else {
            // Fallback to positional mapping: col0=first_name, col1=last_name, col2=email
            $result['first_name'] = isset($row[0]) ? trim((string) $row[0]) : null;
            $result['last_name'] = isset($row[1]) ? trim((string) $row[1]) : null;
            $result['email'] = isset($row[2]) ? trim((string) $row[2]) : null;

            // Normalize empty strings to null
            foreach ($result as $k => $v) {
                if ($v === '') {
                    $result[$k] = null;
                }
            }
        }

        return $result;
    }

    /**
     * Record a per-row error.
     */
    private function recordRowError(ImportJob $import, int $rowNumber, array $row, string $error): void
    {
        ImportError::create([
            'import_job_id' => $import->id,
            'row_number' => $rowNumber,
            'row_data' => $row,
            'error' => $error,
        ]);
    }

    /**
     * Atomically increment processed_rows (and optionally failed_rows).
     */
    private function incrementCounters(ImportJob $import, bool $failed): void
    {
        if ($failed) {
            ImportJob::where('id', $import->id)->update([
                'processed_rows' => DB::raw('processed_rows + 1'),
                'failed_rows' => DB::raw('failed_rows + 1'),
            ]);
        } else {
            ImportJob::where('id', $import->id)->increment('processed_rows');
        }

        $import->refresh();
    }

    /**
     * Finalize the import status based on the results.
     */
    private function finalizeImport(ImportJob $import): void
    {
        $import->refresh();

        // Check for cancellation
        if ($import->isCancelled()) {
            $import->completed_at = now();
            $import->save();

            return;
        }

        $successfulRows = $import->processed_rows - $import->failed_rows;

        if ($successfulRows > 0) {
            $import->status = ImportJob::STATUS_COMPLETED;
        } else {
            // No rows imported successfully
            $import->status = ImportJob::STATUS_FAILED;
            $import->failure_message = 'No contacts were imported successfully. All '.$import->failed_rows.' rows failed validation.';
        }

        $import->completed_at = now();
        $import->save();
    }

    /**
     * Mark the import as failed with a system-level error message.
     */
    private function markFailed(ImportJob $import, string $message): void
    {
        $import->status = ImportJob::STATUS_FAILED;
        $import->failure_message = $message;
        $import->completed_at = now();
        $import->save();
    }
}
