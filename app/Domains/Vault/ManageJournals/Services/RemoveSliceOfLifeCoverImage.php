<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\SliceOfLife;
use App\Services\BaseService;

class RemoveSliceOfLifeCoverImage extends BaseService implements ServiceInterface
{
    private SliceOfLife $slice;

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
            'journal_id' => 'required|integer|exists:journals,id',
            'slice_of_life_id' => 'nullable|integer|exists:slices_of_life,id',
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
     * Remove the slice of life cover image.
     *
     * @param  array  $data
     * @return SliceOfLife
     */
    public function execute(array $data): SliceOfLife
    {
        $this->data = $data;
        $this->validate();

        if ($this->slice->file) {
            $this->slice->file->delete();
        }

        $this->slice->file_cover_image_id = null;
        $this->slice->save();

        return $this->slice;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrFail($this->data['journal_id']);

        $this->slice = $journal->slicesOfLife()
            ->findOrFail($this->data['slice_of_life_id']);
    }
}
