<?php

namespace App\Domains\Contact\ManageQuickFacts\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyQuickFact extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'quick_fact_id' => 'required|integer|exists:quick_facts,id',
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
     * Destroy a quick fact.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $quickFact = $this->contact->quickFacts()
            ->findOrFail($data['quick_fact_id']);

        $quickFact->delete();
    }
}
