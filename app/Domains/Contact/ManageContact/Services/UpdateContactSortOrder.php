<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class UpdateContactSortOrder extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'sort_order' => 'required|string|max:255',
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
            'author_must_be_in_vault',
        ];
    }

    /**
     * Update the way contacts are sorted.
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();

        $this->author->contact_sort_order = $this->data['sort_order'];
        $this->author->save();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }
}
