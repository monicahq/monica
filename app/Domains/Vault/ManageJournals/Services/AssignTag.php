<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Tag;
use App\Services\BaseService;

class AssignTag extends BaseService implements ServiceInterface
{
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
            'tag_id' => 'required|integer|exists:tags,id',
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
     * Assign a tag to the post.
     */
    public function execute(array $data): Tag
    {
        $this->validateRules($data);

        $journal = $this->vault->journals()
            ->findOrFail($data['journal_id']);

        $post = $journal->posts()
            ->findOrFail($data['post_id']);

        $tag = $this->vault->tags()
            ->findOrFail($data['tag_id']);

        $tag->posts()->syncWithoutDetaching($post);

        return $tag;
    }
}
