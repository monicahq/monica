<?php

namespace App\Domains\Vault\ManageVault\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Vault;
use App\Services\BaseService;

class UpdateVaultTabVisibility extends BaseService implements ServiceInterface
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
            'show_group_tab' => 'required|boolean',
            'show_tasks_tab' => 'required|boolean',
            'show_files_tab' => 'required|boolean',
            'show_journal_tab' => 'required|boolean',
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

        $this->vault->show_group_tab = $data['show_group_tab'];
        $this->vault->show_tasks_tab = $data['show_tasks_tab'];
        $this->vault->show_files_tab = $data['show_files_tab'];
        $this->vault->show_journal_tab = $data['show_journal_tab'];
        $this->vault->save();

        return $this->vault;
    }
}
