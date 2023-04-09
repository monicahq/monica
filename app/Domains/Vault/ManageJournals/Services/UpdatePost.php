<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Post;
use App\Services\BaseService;

class UpdatePost extends BaseService implements ServiceInterface
{
    private array $data;

    private Post $post;

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
            'title' => 'nullable|string|max:255',
            'sections' => 'required',
            'written_at' => 'nullable|date_format:Y-m-d',
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
     * Update a post.
     */
    public function execute(array $data): Post
    {
        $this->data = $data;

        $this->validate();
        $this->update();
        $this->updateSections();

        return $this->post;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrFail($this->data['journal_id']);

        $this->post = $journal->posts()
            ->findOrFail($this->data['post_id']);
    }

    private function update(): void
    {
        if (! is_null($this->data['written_at'])) {
            $writtenAt = $this->data['written_at'];
        } else {
            $writtenAt = now();
        }

        $this->post->title = $this->data['title'];
        $this->post->written_at = $writtenAt;
        $this->post->save();
    }

    private function updateSections(): void
    {
        foreach ($this->data['sections'] as $section) {
            if (! array_key_exists('content', $section)) {
                continue;
            }

            $this->post->postSections()
                ->find($section['id'])
                ->update([
                    'content' => $section['content'],
                ]);
        }
    }
}
