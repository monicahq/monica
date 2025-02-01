<?php

namespace App\Domains\Contact\ManageDocuments\Services;

use App\Exceptions\EnvVariablesNotSetException;
use App\Models\File;
use App\Services\BaseService;

class UploadFile extends BaseService
{
    private array $data;

    private File $file;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'uuid' => 'required|string',
            'name' => 'required|string',
            'original_url' => 'required|string',
            'cdn_url' => 'required|string',
            'mime_type' => 'required|string',
            'size' => 'required|integer',
            'type' => 'required|string',
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
     * Upload a file.
     *
     * This doesn't really upload a file though. Upload is handled by Uploadcare.
     * However, we abstract uploads by the File object. This service here takes
     * the payload that Uploadcare sends us back, and map it into a File object
     * that the clients will consume.
     */
    public function execute(array $data): File
    {
        $this->data = $data;
        $this->validate();
        $this->save();

        return $this->file;
    }

    private function validate(): void
    {
        if (is_null(config('services.uploadcare.private_key'))) {
            throw new EnvVariablesNotSetException;
        }

        if (is_null(config('services.uploadcare.public_key'))) {
            throw new EnvVariablesNotSetException;
        }

        $this->validateRules($this->data);
    }

    private function save(): void
    {
        $this->file = File::create([
            'vault_id' => $this->data['vault_id'],
            'uuid' => $this->data['uuid'],
            'name' => $this->data['name'],
            'original_url' => $this->data['original_url'],
            'cdn_url' => $this->data['cdn_url'],
            'mime_type' => $this->data['mime_type'],
            'size' => $this->data['size'],
            'type' => $this->data['type'],
        ]);
    }
}
