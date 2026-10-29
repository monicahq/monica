<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;

use App\Domains\Contact\ManageContact\Services\ImportFile;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ImportJobResource;
use App\Models\ImportJob;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class ContactImportController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show', 'errorsCsv']);
        $this->middleware('abilities:write')->only(['store']);

        parent::__construct();
    }

    public function index(Request $request)
    {
        $importJobs = ImportJob::where('account_id', $request->user()->account_id)
            ->orderBy('created_at', 'desc')
            ->paginate($this->getLimitPerPage());

        return ImportJobResource::collection($importJobs);
    }

    public function show(Request $request, string $importJobId)
    {
        $importJob = ImportJob::findOrFail($importJobId);
        $vault = $importJob->vault;

        if ($request->user()->account_id !== $vault->account_id) {
            return $this->respondNotFound();
        }

        $this->syncBatchStatus($importJob);

        return new ImportJobResource($importJob);
    }

    public function errorsCsv(Request $request, string $importJobId)
    {
        $importJob = ImportJob::findOrFail($importJobId);
        $vault = $importJob->vault;

        if ($request->user()->account_id !== $vault->account_id) {
            return $this->respondNotFound();
        }

        $errors = $importJob->errors ?? [];

        if (empty($errors)) {
            return response()->make('No errors found', 404);
        }

        if (! Storage::exists($importJob->file_path)) {
            return response()->make('Original file not found', 404);
        }

        $stream = Storage::readStream($importJob->file_path);
        $header = null;
        $allRows = [];
        $lineNumber = 0;

        while (($line = fgetcsv($stream, 0, ',')) !== false) {
            $line = array_map('trim', $line);
            if (array_filter($line) === []) {
                continue;
            }
            if ($header === null) {
                $header = $line;

                continue;
            }
            $lineNumber++;
            if (count($line) === count($header)) {
                $allRows[$lineNumber] = array_combine($header, $line);
            }
        }

        fclose($stream);

        $errorByRow = [];
        foreach ($errors as $error) {
            $errorByRow[$error['row']] = $error['message'];
        }

        $escapeCsv = function ($value) {
            return '"'.str_replace('"', '""', $value).'"';
        };

        $headerColumns = $header ?? [];
        $csv = implode(',', array_map($escapeCsv, array_merge($headerColumns, ['error'])))."\n";

        foreach ($errorByRow as $rowNum => $errorMsg) {
            if (isset($allRows[$rowNum])) {
                $row = $allRows[$rowNum];
                $values = [];
                foreach ($headerColumns as $col) {
                    $values[] = $escapeCsv($row[$col] ?? '');
                }
                $values[] = $escapeCsv($errorMsg);
                $csv .= implode(',', $values)."\n";
            }
        }

        return response()->make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import_errors_'.$importJob->id.'.csv"',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200',
            ],
            'vault_id' => 'required|string|exists:vaults,id',
        ]);

        $vault = Vault::findOrFail($request->input('vault_id'));

        if ($request->user()->account_id !== $vault->account_id) {
            return $this->respondNotFound();
        }

        $file = $request->file('file');
        $contentHash = hash_file('sha256', $file->getRealPath());

        $existing = ImportJob::where('vault_id', $vault->id)
            ->where('content_hash', $contentHash)
            ->where('status', 'completed')
            ->exists();

        if ($existing) {
            return $this->setHTTPStatusCode(422)
                ->setErrorCode(41)
                ->respondWithError('This file has already been imported.');
        }

        $originalName = $file->getClientOriginalName();
        $filePath = $file->store('imports');

        try {
            $importJob = (new ImportFile)->execute([
                'account_id' => $request->user()->account_id,
                'author_id' => $request->user()->id,
                'vault_id' => $vault->id,
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'file_size' => $file->getSize(),
                'content_hash' => $contentHash,
                'file_type' => 'csv',
            ]);
        } catch (\InvalidArgumentException $e) {
            Storage::delete($filePath);

            return $this->setHTTPStatusCode(422)
                ->setErrorCode(41)
                ->respondWithError($e->getMessage());
        }

        return (new ImportJobResource($importJob))
            ->response()
            ->setStatusCode(201);
    }

    private function syncBatchStatus(ImportJob $importJob): void
    {
        if ($importJob->status !== 'processing') {
            return;
        }

        $batch = $importJob->batch_id ? Bus::findBatch($importJob->batch_id) : null;

        if (! $batch) {
            return;
        }

        if ($batch->cancelled()) {
            $importJob->update(['status' => 'cancelled', 'completed_at' => now()]);
        } elseif ($batch->finished() && ! $batch->hasFailures()) {
            $importJob->refresh();
            if ($importJob->processed_rows === 0 && $importJob->total_rows > 0) {
                $importJob->update(['status' => 'failed', 'completed_at' => now()]);
            } else {
                $importJob->update(['status' => 'completed', 'completed_at' => now()]);
            }
        } elseif ($batch->hasFailures()) {
            $importJob->update(['status' => 'failed', 'completed_at' => now()]);
        }
    }
}
