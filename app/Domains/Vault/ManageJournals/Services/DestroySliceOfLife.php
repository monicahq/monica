<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\SliceOfLife;
use App\Services\BaseService;

class DestroySliceOfLife extends BaseService implements ServiceInterface
{
    private array $data;

    private SliceOfLife $sliceOfLife;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'journal_id' => 'required|integer|exists:journals,id',
            'slice_of_life_id' => 'required|integer|exists:slice_of_lives,id',
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
        ];
    }

    /**
     * Delete a slice of life.
     */
    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validate();
        $this->sliceOfLife->delete();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrFail($this->data['journal_id']);

        $this->sliceOfLife = $journal->slicesOfLife()
            ->findOrFail($this->data['slice_of_life_id']);
    }
}
