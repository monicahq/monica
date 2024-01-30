<?php

namespace App\Domains\Contact\ManageGifts\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Gift;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UpdateGift extends BaseService implements ServiceInterface
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
            'gift_id' => 'required|integer|exists:gifts,id',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a gift.
     */
    public function execute(array $data): Gift
    {
        $this->validateRules($data);

        $this->gift = $this->contact->gifts()
            ->findOrFail($data['gift_id']);

        $this->gift->description = $data['description'];
        $this->gift->name = $data['name'];
        $this->gift->type = $data['type'];
        $this->gift->save();

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
            'action' => ContactFeedItem::ACTION_GIFT_UPDATED,
            'description' => Str::words($this->gift->description, 10, '…'),
        ]);
        $this->gift->feedItem()->save($feedItem);
    }
}
