<?php

namespace App\Domains\Vault\ManageVault\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Vault;
use App\Services\BaseService;

class UpdateVaultTabVisibility extends BaseService implements ServiceInterface
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
            'show_group_tab' => 'required|boolean',
            'show_tasks_tab' => 'required|boolean',
            'show_files_tab' => 'required|boolean',
            'show_journal_tab' => 'required|boolean',
            'show_companies_tab' => 'required|boolean',
            'show_reports_tab' => 'required|boolean',
            'show_calendar_tab' => 'required|boolean',
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
     * Update a vault.
     */
    public function execute(array $data): Vault
    {
        $this->validateRules($data);

        $this->vault->show_group_tab = $data['show_group_tab'];
        $this->vault->show_tasks_tab = $data['show_tasks_tab'];
        $this->vault->show_files_tab = $data['show_files_tab'];
        $this->vault->show_journal_tab = $data['show_journal_tab'];
        $this->vault->show_companies_tab = $data['show_companies_tab'];
        $this->vault->show_reports_tab = $data['show_reports_tab'];
        $this->vault->show_calendar_tab = $data['show_calendar_tab'];
        $this->vault->save();

        return $this->vault;
    }
}
