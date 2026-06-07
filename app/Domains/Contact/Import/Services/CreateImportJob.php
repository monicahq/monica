<?php

namespace App\Domains\Contact\Import\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ImportJob;
use App\Services\BaseService;
use App\Jobs\ProcessImportJob;
use App\Exceptions\DuplicateImportJobException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreateImportJob extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id'  => 'required|uuid|exists:users,id',
            'vault_id'   => 'required|uuid',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Store the uploaded file, detect duplicates, create the import job record,
     * and dispatch the background orchestrator.
     *
     * @throws DuplicateImportJobException
     */
    public function execute(array $data): ImportJob
    {
        $this->validateRules($data);

        /** @var UploadedFile $file */
        $file = $data['file'];

        // Compute content hash for idempotency / duplicate detection
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Persist to disk before any database work
        $filePath = $file->store('imports', 'local');

        try {
            // Reject duplicate uploads within the last 24 hours for the same vault
            $duplicate = ImportJob::where('vault_id', $data['vault_id'])
                ->where('file_hash', $fileHash)
                ->where('created_at', '>=', now()->subDay())
                ->first();

            if ($duplicate) {
                throw new DuplicateImportJobException(
                    "This file has already been uploaded for import in the last 24 hours. Duplicate import job ID: {$duplicate->id}"
                );
            }

            $importJob = ImportJob::create([
                'account_id' => $data['account_id'],
                'user_id'    => $data['author_id'],
                'vault_id'   => $data['vault_id'],
                'filename'   => $file->getClientOriginalName(),
                'file_path'  => $filePath,
                'file_hash'  => $fileHash,
                'total_rows' => 0,
                'status'     => \App\Enums\ImportJobStatus::PENDING,
            ]);

            ProcessImportJob::dispatch($importJob->id);

            return $importJob;
        } catch (\Throwable $e) {
            // Clean up the stored file so disk space is not leaked on any failure
            Storage::disk('local')->delete($filePath);

            throw $e;
        }
    }
}
