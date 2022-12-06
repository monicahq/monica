<?php

namespace App\Domains\Vault\ManageVault\Services;

use App\Interfaces\ServiceInterface;
use App\Models\File;
use App\Services\BaseService;

class DestroyVault extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'vault_id' => 'required|integer|exists:vaults,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $contactIds = $this->vault->contacts()
            ->pluck('id')
            ->toArray();

        File::whereIn('contact_id', $contactIds)->chunk(100, function ($files) {
            $files->each(function ($file) {
                $file->delete();
            });
        });

        $this->vault->delete();
    }
}
