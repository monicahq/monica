<?php

namespace App\Domains\Import\ManageImport\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\ProcessImportJob;
use App\Models\ImportJob;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InitiateImport extends BaseService implements ServiceInterface
{
    private array $data;
    private ImportJob $importJob;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'filename' => 'required|string',
            'file_path' => 'required|string',
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
     * Create an import job and dispatch the background processor.
     */
    public function execute(array $data): ImportJob
    {
        $this->data = $data;

        $this->validateRules($this->data);
        
        $filePath = Storage::path($this->data['file_path']);
        $fileHash = md5_file($filePath);

        $duplicateJob = ImportJob::where('account_id', $this->data['account_id'])
            ->where('vault_id', $this->data['vault_id'])
            ->where('file_hash', $fileHash)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->exists();

        if ($duplicateJob) {
            Storage::delete($this->data['file_path']);
            throw ValidationException::withMessages([
                'file' => 'This file has already been uploaded for this vault.'
            ]);
        }
        
        $this->importJob = ImportJob::create([
            'account_id' => $this->data['account_id'],
            'user_id' => $this->data['author_id'],
            'vault_id' => $this->data['vault_id'],
            'filename' => $this->data['filename'],
            'file_path' => $this->data['file_path'],
            'file_hash' => $fileHash,
            'status' => 'pending',
        ]);

        ProcessImportJob::dispatch($this->importJob);

        return $this->importJob;
    }
}
