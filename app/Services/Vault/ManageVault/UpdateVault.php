<?php

namespace App\Services\Vault\ManageVault;

use App\Models\Vault;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class UpdateVault extends BaseService implements ServiceInterface
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
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
     * Update a vault.
     *
     * @param  array  $data
     * @return Vault
     */
    public function execute(array $data): Vault
    {
        $this->validateRules($data);

        $this->vault->name = $data['name'];
        $this->vault->description = $this->valueOrNull($data, 'description');
        $this->vault->save();

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'vault_updated',
            'objects' => json_encode([
                'vault_id' => $this->vault->id,
                'vault_name' => $this->vault->name,
            ]),
        ])->onQueue('low');

        return $this->vault;
    }
}
