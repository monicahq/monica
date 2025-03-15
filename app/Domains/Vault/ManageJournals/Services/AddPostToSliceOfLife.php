<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Post;
use App\Models\SliceOfLife;
use App\Services\BaseService;

class AddPostToSliceOfLife extends BaseService implements ServiceInterface
{
    private Post $post;

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
            'journal_id' => 'required|integer|exists:journals,id',
            'post_id' => 'required|integer|exists:posts,id',
            'slice_of_life_id' => 'nullable|integer|exists:slice_of_lives,id',
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
     * Add a post to a slice of life.
     */
    public function execute(array $data): SliceOfLife
    {
        $this->data = $data;
        $this->validate();

        $this->post->slice_of_life_id = optional($this->slice)->id;
        $this->post->save();

        return $this->slice;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrFail($this->data['journal_id']);

        $this->post = $journal->posts()
            ->findOrFail($this->data['post_id']);

        if ($this->valueOrNull($this->data, 'slice_of_life_id')) {
            $this->slice = $journal->slicesOfLife()
                ->findOrFail($this->data['slice_of_life_id']);
        }
    }
}
