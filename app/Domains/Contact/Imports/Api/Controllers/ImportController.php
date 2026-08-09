<?php

namespace App\Domains\Contact\Imports\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Jobs\ProcessImportJob;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends ApiController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        parent::__construct();
    }

    /**
     * Upload a CSV file and begin background import processing.
     *
     * POST /api/import
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // max 10MB
            'vault_id' => 'required|string|exists:vaults,id',
        ]);

        $user = $request->user();
        $account = $user->account;

        // Verify the vault belongs to the user's account
        $vault = $account->vaults()->findOrFail($request->input('vault_id'));

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Compute file hash for duplicate detection (bonus)
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Store file in a non-public location: storage/app/imports/{account_id}/{random}-{filename}
        $storedPath = $file->storeAs(
            'imports/'.$account->id,
            Str::random(40).'-'.basename($originalName)
        );

        // Create the import record with pending status
        $import = ImportJob::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'vault_id' => $vault->id,
            'filename' => $originalName,
            'file_path' => $storedPath,
            'file_hash' => $fileHash,
            'total_rows' => 0,
            'processed_rows' => 0,
            'failed_rows' => 0,
            'status' => ImportJob::STATUS_PENDING,
        ]);

        // Dispatch the queued job (contacts are NOT processed inside the HTTP request)
        ProcessImportJob::dispatch($import->id);

        return $this->setHTTPStatusCode(201)->respond([
            'data' => $this->formatImportData($import),
        ]);
    }

    /**
     * Return the current import status and progress.
     *
     * GET /api/import/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Enforce authentication and account ownership
        $import = $user->account->importJobs()->findOrFail($id);

        return $this->respond([
            'data' => $this->formatImportData($import),
        ]);
    }

    /**
     * Cancel a running import.
     *
     * POST /api/import/{id}/cancel
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $import = $user->account->importJobs()->findOrFail($id);

        if (! in_array($import->status, [ImportJob::STATUS_PENDING, ImportJob::STATUS_PROCESSING])) {
            return $this->setHTTPStatusCode(422)->respondWithError(
                'Import cannot be cancelled. Current status: '.$import->status
            );
        }

        $import->status = ImportJob::STATUS_CANCELLED;
        $import->completed_at = now();
        $import->save();

        return $this->respond([
            'data' => $this->formatImportData($import),
        ]);
    }

    /**
     * Download a CSV of failed rows and their errors.
     *
     * GET /api/import/{id}/errors
     */
    public function errors(Request $request, string $id)
    {
        $user = $request->user();
        $import = $user->account->importJobs()->findOrFail($id);

        $errors = $import->errors()->orderBy('row_number')->get();

        if ($errors->isEmpty()) {
            return $this->respond(['data' => ['message' => 'No errors found for this import.']]);
        }

        // Build CSV content
        $csvLines = [];
        $csvLines[] = implode(',', ['row_number', 'error', 'row_data']);

        foreach ($errors as $error) {
            $rowData = is_array($error->row_data) ? implode(' | ', $error->row_data) : '';
            $csvLines[] = implode(',', [
                $error->row_number,
                '"'.str_replace('"', '""', $error->error).'"',
                '"'.str_replace('"', '""', $rowData).'"',
            ]);
        }

        $csvContent = implode("\n", $csvLines);
        $filename = 'import_'.$import->id.'_errors.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Format an ImportJob model into the API response structure.
     */
    private function formatImportData(ImportJob $import): array
    {
        return [
            'id' => $import->id,
            'filename' => $import->filename,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'failed_rows' => $import->failed_rows,
            'status' => $import->status,
            'progress_pct' => $import->progressPercent(),
            'failure_message' => $import->failure_message,
            'started_at' => $import->started_at?->toIso8601String(),
            'completed_at' => $import->completed_at?->toIso8601String(),
            'created_at' => $import->created_at?->toIso8601String(),
        ];
    }
}
