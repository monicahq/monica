<?php

namespace App\Domains\Contact\ManageQuickFacts\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class ToggleQuickFactModule extends BaseService implements ServiceInterface
{
    private array $data;

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
            'contact_id' => 'required|integer|exists:contacts,id',
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
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Toggle the quick facts window for the given contact.
     *
     * @param  array  $data
     * @return void
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
