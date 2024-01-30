<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Gift;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CreateGift extends BaseService implements ServiceInterface
{
    private Gift $gift;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'description' => 'required|string|max:65535',
            'name' => 'required|string|max:255',
            'type' => 'required',
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
     * Create a note.
     */
    public function execute(array $data): Gift
    {
        $this->validateRules($data);

        $this->gift = Gift::create([
            'contact_id' => $this->contact->id,
            'vault_id' => $data['vault_id'],
            'author_id' => $this->author->id,
            'description' => $data['description'],
            'type' => $data['type'],
            'name' => $data['name'],
        ]);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();

        return $this->gift;
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_GIFT_CREATED,
            'description' => Str::words($this->gift->body, 10, '…'),
        ]);
        $this->gift->feedItem()->save($feedItem);
    }
}
