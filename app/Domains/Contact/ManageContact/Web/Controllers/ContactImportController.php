<?php

namespace App\Domains\Contact\ManageContact\Web\Controllers;

use App\Domains\Contact\ManageContact\Services\ImportFile;
use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactImportViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContactImportController extends Controller
{
    public function create(Request $request, Vault $vault)
    {
        Gate::authorize('vault-editor', $vault);

        return Inertia::render('Vault/Contact/Import', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactImportViewHelper::data($vault),
        ]);
    }

    public function store(Request $request, Vault $vault)
    {
        Gate::authorize('vault-editor', $vault);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:vcf,vcard,csv,txt',
                'max:51200',
            ],
        ]);

        $file = $request->file('file');
        $contentHash = hash_file('sha256', $file->getRealPath());

        $existing = ImportJob::where('vault_id', $vault->id)
            ->where('content_hash', $contentHash)
            ->where('status', 'completed')
            ->exists();

        if ($existing) {
            return response()->json([
                'errors' => [
                    'file' => [trans('This file has already been imported.')],
                ],
            ], 422);
        }

        $originalName = $file->getClientOriginalName();
        $filePath = $file->store('imports');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = in_array($extension, ['csv', 'txt']) ? 'csv' : 'vcard';

        try {
            $importJob = (new ImportFile)->execute([
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::id(),
                'vault_id' => $vault->id,
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'file_size' => $file->getSize(),
                'content_hash' => $contentHash,
                'file_type' => $fileType,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => trans($e->getMessage())]);
        }

        return response()->json([
            'data' => route('contact.import.show', [
                'vault' => $vault->id,
                'importJob' => $importJob->id,
            ]),
        ], 201);
    }

    public function cancel(Request $request, Vault $vault, ImportJob $importJob)
    {
        Gate::authorize('vault-editor', $vault);

        if (! in_array($importJob->status, ['pending', 'processing'])) {
            return response()->json(['message' => trans('Import cannot be cancelled.')], 409);
        }

        $importJob->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        if ($importJob->batch_id) {
            Bus::findBatch($importJob->batch_id)->cancel();
        }

        return response()->json(['status' => 'cancelled']);
    }

    public function show(Request $request, Vault $vault, ImportJob $importJob)
    {
        Gate::authorize('vault-editor', $vault);

        return Inertia::render('Vault/Contact/Import', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => ContactImportViewHelper::data($vault),
            'importJob' => [
                'id' => $importJob->id,
                'status' => $importJob->status,
                'total_rows' => $importJob->total_rows,
                'processed_rows' => $importJob->processed_rows,
                'failed_rows' => $importJob->failed_rows,
                'skipped_rows' => $importJob->skipped_rows,
                'original_filename' => $importJob->original_filename,
                'error_log' => $importJob->error_log,
                'created_at' => $importJob->created_at->toISOString(),
                'completed_at' => $importJob->completed_at?->toISOString(),
                'errors_csv_url' => $importJob->errors
                    ? route('contact.import.errors.csv', ['vault' => $vault->id, 'importJob' => $importJob->id])
                    : null,
            ],
        ]);
    }

    public function progress(Request $request, Vault $vault, ImportJob $importJob)
    {
        Gate::authorize('vault-editor', $vault);

        $status = $importJob->status;
        if ($status === 'processing') {
            $batch = $importJob->batch_id ? Bus::findBatch($importJob->batch_id) : null;
            if ($batch && $batch->cancelled()) {
                $importJob->update(['status' => 'cancelled', 'completed_at' => now()]);
                $status = 'cancelled';
            } elseif ($batch && $batch->finished() && ! $batch->hasFailures()) {
                $importJob->refresh();
                if ($importJob->processed_rows === 0 && $importJob->total_rows > 0) {
                    $importJob->update(['status' => 'failed', 'completed_at' => now()]);
                    $status = 'failed';
                } else {
                    $importJob->update(['status' => 'completed', 'completed_at' => now()]);
                    $status = 'completed';
                }
            } elseif ($batch && $batch->hasFailures()) {
                $importJob->update(['status' => 'failed', 'completed_at' => now()]);
                $status = 'failed';
            }
        }

        return response()->json([
            'id' => $importJob->id,
            'status' => $status,
            'total_rows' => $importJob->total_rows,
            'processed_rows' => $importJob->processed_rows,
            'failed_rows' => $importJob->failed_rows,
            'skipped_rows' => $importJob->skipped_rows,
            'error_log' => $importJob->error_log,
            'errors' => $importJob->errors,
            'completed_at' => $importJob->completed_at?->toISOString(),
            'errors_csv_url' => $importJob->errors
                ? route('contact.import.errors.csv', ['vault' => $vault->id, 'importJob' => $importJob->id])
                : null,
        ]);
    }

    public function errorsCsv(Request $request, Vault $vault, ImportJob $importJob)
    {
        Gate::authorize('vault-editor', $vault);

        $errors = $importJob->errors ?? [];

        if (empty($errors)) {
            return response()->make('No errors found', 404);
        }

        $filePath = $importJob->file_path;
        if (! Storage::exists($filePath)) {
            return response()->make('Original file not found', 404);
        }

        $stream = Storage::readStream($filePath);
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
}
