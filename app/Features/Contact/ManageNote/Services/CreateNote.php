<?php

namespace App\Features\Contact\ManageNote\Services;

use App\Models\Note;
use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Models\ContactFeedItem;
use App\Interfaces\ServiceInterface;

class CreateNote extends BaseService implements ServiceInterface
{
    private Note $note;

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
            'contact_id' => 'required|integer|exists:contacts,id',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:65535',
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
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a note.
     *
     * @param  array  $data
     * @return Note
     */
    public function execute(array $data): Note
    {
        $this->validateRules($data);

        $this->note = Note::create([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'title' => $this->valueOrNull($data, 'title'),
            'body' => $data['body'],
        ]);

        $this->log();

        $this->createFeedItem();

        return $this->note;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'note_created',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'note_id' => $this->note->id,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'note_created',
            'objects' => json_encode([
                'note_id' => $this->note->id,
            ]),
        ]);
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'contact_id' => $this->contact->id,
        ]);
        $this->note->feedItem()->save($feedItem);
    }
}
