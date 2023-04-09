<?php

namespace App\Domains\Vault\ManageVaultSettings\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Vault;
use App\Services\BaseService;

class UpdateVaultDefaultTemplate extends BaseService implements ServiceInterface
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
            'template_id' => 'required|integer|exists:templates,id',
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
     * Update the vault's default template.
     */
    public function execute(array $data): Vault
    {
        $this->validateRules($data);

        if ($this->valueOrNull($data, 'template_id')) {
            $this->account()->templates()
                ->findOrFail($data['template_id']);
        }

        $this->vault->default_template_id = $this->valueOrNull($data, 'template_id');
        $this->vault->save();

        return $this->vault;
    }
}
