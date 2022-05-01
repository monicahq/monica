<?php

namespace App\Vault\ManageCompanies\Services;

use App\Models\Company;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreateCompany extends BaseService implements ServiceInterface
{
    private array $data;
    private Company $company;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
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
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Create a company.
     *
     * @param  array  $data
     * @return Company
     */
    public function execute(array $data): Company
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->company;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        $this->company = Company::create([
            'vault_id' => $this->data['vault_id'],
            'name' => $this->data['name'],
            'type' => $this->valueOrNull($this->data, 'type'),
        ]);
    }
}
