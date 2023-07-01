<?php

namespace App\Domains\Vault\ManageJournals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Post;
use App\Services\BaseService;
use Carbon\Carbon;

class AddContactToPost extends BaseService implements ServiceInterface
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
            'contact_id' => 'required|uuid|exists:contacts,id',
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
     * Add a contact to a post.
     */
    public function execute(array $data): Post
    {
        $this->data = $data;
        $this->validate();

        $this->post->contacts()->syncWithoutDetaching($this->contact);

        $this->createFeedItem();
        $this->updateLastEditedDate();

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

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_ADDED_TO_POST,
            'description' => $this->post->title,
        ]);
        $this->post->feedItem()->save($feedItem);
    }
}
