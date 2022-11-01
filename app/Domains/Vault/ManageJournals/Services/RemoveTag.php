<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Journal;
use App\Models\Post;
use App\Models\Tag;
use App\Services\BaseService;

class RemoveTag extends BaseService implements ServiceInterface
{
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
            'post_id' => 'required|integer|exists:posts,id',
            'tag_id' => 'required|integer|exists:tags,id',
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
     * Remove a tag from the post.
     *
     * @param  array  $data
     * @return Tag
     */
    public function execute(array $data): Tag
    {
        $this->validateRules($data);

        Journal::where('vault_id', $data['vault_id'])
            ->findOrFail($data['journal_id']);

        $post = Post::where('journal_id', $data['journal_id'])
            ->findOrFail($data['post_id']);

        $tag = Tag::where('vault_id', $data['vault_id'])
            ->findOrFail($data['tag_id']);

        $post->tags()->detach($tag);

        return $tag;
    }
}
