<?php

namespace App\Domains\Contact\ManageContactAddresses\Services;

use App\Domains\Contact\ManageContactAddresses\Jobs\FetchAddressGeocoding;
use App\Helpers\MapHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Models\ContactFeedItem;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateContactAddress extends BaseService implements ServiceInterface
{
    private Address $address;

    private array $data;

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
            'address_id' => 'required|integer|exists:addresses,id',
            'address_type_id' => 'nullable|integer|exists:address_types,id',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'lived_from_at' => 'nullable|date_format:Y-m-d',
            'lived_until_at' => 'nullable|date_format:Y-m-d',
            'is_past_address' => 'nullable|boolean',
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
     * Update a contact address.
     *
     * @param  array  $data
     * @return Address
     */
    public function execute(array $data): Address
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->address;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        if ($this->valueOrNull($this->data, 'address_type_id')) {
            $this->account()->addressTypes()
                ->findOrFail($this->data['address_type_id']);
        }

        $this->address = $this->contact->addresses()
            ->findOrFail($this->data['address_id']);
    }

    private function update(): void
    {
        $this->address->address_type_id = $this->valueOrNull($this->data, 'address_type_id');
        $this->address->street = $this->valueOrNull($this->data, 'street');
        $this->address->city = $this->valueOrNull($this->data, 'city');
        $this->address->province = $this->valueOrNull($this->data, 'province');
        $this->address->postal_code = $this->valueOrNull($this->data, 'postal_code');
        $this->address->country = $this->valueOrNull($this->data, 'country');
        $this->address->latitude = $this->valueOrNull($this->data, 'latitude');
        $this->address->longitude = $this->valueOrNull($this->data, 'longitude');
        $this->address->lived_from_at = $this->valueOrNull($this->data, 'lived_from_at');
        $this->address->lived_until_at = $this->valueOrNull($this->data, 'lived_until_at');
        $this->address->is_past_address = $this->valueOrFalse($this->data, 'is_past_address');
        $this->address->save();

        $this->geocodeAddress();

        $this->createFeedItem();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function geocodeAddress(): void
    {
        FetchAddressGeocoding::dispatch($this->address)->onQueue('low');
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_ADDRESS_UPDATED,
            'description' => MapHelper::getAddressAsString($this->address),
        ]);

        $this->address->feedItem()->save($feedItem);
    }
}
