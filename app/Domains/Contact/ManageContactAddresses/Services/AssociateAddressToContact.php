<?php

namespace App\Domains\Contact\ManageContactAddresses\Services;

use App\Helpers\MapHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Models\ContactFeedItem;
use App\Services\BaseService;
use Carbon\Carbon;

class AssociateAddressToContact extends BaseService implements ServiceInterface
{
    private Address $address;

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
            'contact_id' => 'required|uuid|exists:contacts,id',
            'address_id' => 'required|integer|exists:addresses,id',
            'is_past_address' => 'required|boolean',
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
     * Associate an address to a contact.
     */
    public function execute(array $data): Address
    {
        $this->data = $data;
        $this->validate();
        $this->match();
        $this->createFeedItem();

        return $this->address;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->address = $this->vault->addresses()->findOrFail($this->data['address_id']);
    }

    private function match(): void
    {
        $this->contact->addresses()->attach($this->address,
            ['is_past_address' => $this->data['is_past_address']]
        );
    }

    private function createFeedItem(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_ADDRESS_CREATED,
            'description' => MapHelper::getAddressAsString($this->address),
        ]);

        $this->address->feedItem()->save($feedItem);
    }
}
