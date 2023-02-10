<?php

namespace App\Domains\Vault\ManageVault\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Vault;
use App\Services\BaseService;

class UpdateVaultDashboardDefaultTab extends BaseService implements ServiceInterface
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
            'show_activity_tab_on_dashboard' => 'required|boolean',
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
            'author_must_be_in_vault',
        ];
    }

    /**
     * Update a vault's default tab displayed on the dashboard.
     *
     * @param  array  $data
     * @return Vault
     */
    public function execute(array $data): Vault
    {
        $this->validateRules($data);

        $this->vault->show_activity_tab_on_dashboard = $data['show_activity_tab_on_dashboard'];
        $this->vault->save();

        return $this->vault;
    }
}
