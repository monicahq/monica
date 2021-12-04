<?php

namespace App\Features\Contact\ManageNote\Services;

use App\Models\Note;
use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Interfaces\ServiceInterface;

class DestroyNote extends BaseService implements ServiceInterface
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'note_id' => 'required|integer|exists:notes,id',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Destroy a note.
     *
     * @param array $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->note = Note::where('contact_id', $data['contact_id'])
            ->findOrFail($data['note_id']);

        $this->removeContactFeedItem();

        $this->note->delete();

        $this->log();
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'note_destroyed',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'note_destroyed',
            'objects' => json_encode([]),
        ]);
    }

    private function removeContactFeedItem(): void
    {
        $this->note->feedItem->delete();
    }
}
