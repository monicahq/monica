<?php

namespace App\Domains\Contact\ManageQuickFacts\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class ToggleQuickFactModule extends BaseService implements ServiceInterface
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
            'contact_id' => 'required|uuid|exists:contacts,id',
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Toggle the quick facts window for the given contact.
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();
        $this->update();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function update(): void
    {
        $this->contact->show_quick_facts = ! $this->contact->show_quick_facts;
        $this->contact->save();
    }
}
