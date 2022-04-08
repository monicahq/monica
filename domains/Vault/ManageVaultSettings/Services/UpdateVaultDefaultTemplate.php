<?php

namespace App\Vault\ManageVaultSettings\Services;

use App\Models\Vault;
use App\Models\Template;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class UpdateVaultDefaultTemplate extends BaseService implements ServiceInterface
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
            'template_id' => 'required|integer|exists:templates,id',
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
     * Update the vault's default template.
     *
     * @param  array  $data
     * @return Vault
     */
    public function execute(array $data): Vault
    {
        $this->validateRules($data);

        if ($this->valueOrNull($data, 'template_id')) {
            Template::where('account_id', $data['account_id'])
                ->findOrFail($data['template_id']);
        }

        $this->vault->default_template_id = $this->valueOrNull($data, 'template_id');
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
