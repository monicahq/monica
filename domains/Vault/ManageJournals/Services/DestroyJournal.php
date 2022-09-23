<?php

namespace App\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Journal;
use App\Services\BaseService;

class DestroyJournal extends BaseService implements ServiceInterface
{
    private array $data;

    private Journal $journal;

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
            'journal_id' => 'required|integer|exists:journals,id',
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
     * Delete a journal.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validate();
        $this->journal->delete();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->journal = Journal::where('vault_id', $this->data['vault_id'])
            ->findOrFail($this->data['journal_id']);
    }
}
