<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\SliceOfLife;
use App\Services\BaseService;

class CreateSliceOfLife extends BaseService implements ServiceInterface
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
            'name' => 'nullable|string|max:255',
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
     * Create a slice of life.
     */
    public function execute(array $data): SliceOfLife
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->sliceOfLife;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->vault->journals()
            ->findOrfail($this->data['journal_id']);
    }

    private function create(): void
    {
        $this->sliceOfLife = SliceOfLife::create([
            'journal_id' => $this->data['journal_id'],
            'name' => $this->data['name'],
        ]);
    }
}
