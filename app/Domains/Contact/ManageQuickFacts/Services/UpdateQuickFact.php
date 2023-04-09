<?php

namespace App\Domains\Contact\ManageQuickFacts\Services;

use App\Interfaces\ServiceInterface;
use App\Models\QuickFact;
use App\Services\BaseService;

class UpdateQuickFact extends BaseService implements ServiceInterface
{
    private QuickFact $quickFact;

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
            'quick_fact_id' => 'required|integer|exists:quick_facts,id',
            'content' => 'required|string|max:255',
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
     * Update a quick fact.
     */
    public function execute(array $data): QuickFact
    {
        $this->data = $data;
        $this->validate();

        $this->quickFact->content = $this->data['content'];
        $this->quickFact->save();

        return $this->quickFact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->quickFact = $this->contact->quickFacts()
            ->findOrFail($this->data['quick_fact_id']);
    }
}
