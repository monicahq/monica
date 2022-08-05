<?php

namespace App\Contact\ManageNotes\Services;

use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\ContactFeedItem;
use App\Models\Emotion;
use App\Models\Note;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
            'emotion_id' => 'nullable|integer|exists:emotions,id',
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

        if ($this->valueOrNull($data, 'emotion_id')) {
            Emotion::where('account_id', $data['account_id'])
                ->where('id', $data['emotion_id'])
                ->firstOrFail();
        }

        $this->note = Note::create([
            'contact_id' => $this->contact->id,
            'vault_id' => $data['vault_id'],
            'author_id' => $this->author->id,
            'title' => $this->valueOrNull($data, 'title'),
            'body' => $data['body'],
            'emotion_id' => $this->valueOrNull($data, 'emotion_id'),
        ]);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

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
        ])->onQueue('low');

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'note_created',
            'objects' => json_encode([
                'note_id' => $this->note->id,
            ]),
        ])->onQueue('low');
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_NOTE_CREATED,
            'description' => Str::words($this->note->body, 10, '…'),
        ]);
        $this->note->feedItem()->save($feedItem);
    }
}
