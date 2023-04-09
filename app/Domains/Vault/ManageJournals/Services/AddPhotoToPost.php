<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\File;
use App\Models\Post;
use App\Services\BaseService;

class AddPhotoToPost extends BaseService implements ServiceInterface
{
    private Post $post;

    private array $data;

    private File $file;

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
            'post_id' => 'nullable|integer|exists:posts,id',
            'file_id' => 'required|integer|exists:files,id',
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
     * Add an image to a post.
     */
    public function execute(array $data): Post
    {
        $this->data = $data;
        $this->validate();

        $this->file->fileable_id = $this->post->id;
        $this->file->fileable_type = Post::class;
        $this->file->type = File::TYPE_PHOTO;
        $this->file->save();

        return $this->post;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $journal = $this->vault->journals()
            ->findOrFail($this->data['journal_id']);

        $this->post = $journal->posts()
            ->findOrFail($this->data['post_id']);

        $this->file = $this->vault->files()
            ->findOrFail($this->data['file_id']);
    }
}
