<?php

namespace App\Domains\Vault\ManageVault\Services;

use App\Interfaces\ServiceInterface;
use App\Models\File;
use App\Services\BaseService;

class DestroyVault extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
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
            'author_must_be_vault_manager',
        ];
    }

    /**
     * Destroy a vault.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        // we need to delete all the files in the vault this way, so the event
        // FileDeleted is called and delete the file from storage
        File::where('vault_id', $data['vault_id'])->chunk(100, function ($files) {
            $files->each(function ($file) {
                $file->delete();
            });
        });

        $this->vault->delete();
    }
}
