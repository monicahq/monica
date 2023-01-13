<?php

namespace App\Domains\Contact\ManageContactAddresses\Services;

use App\Domains\Contact\ManageContactAddresses\Jobs\FetchAddressGeocoding;
use App\Helpers\MapHelper;
use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Models\ContactFeedItem;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactAddress extends BaseService implements ServiceInterface
{
    private Address $address;

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
            'address_type_id' => 'nullable|integer|exists:address_types,id',
            'line_1' => 'nullable|string|max:255',
            'line_2' => 'nullable|string|max:255',
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a contact address.
     *
     * @param  array  $data
     * @return Address
     */
    public function execute(array $data): Address
    {
        $this->validateRules($data);

        if ($this->valueOrNull($data, 'address_type_id')) {
            $this->account()->addressTypes()
                ->findOrFail($data['address_type_id']);
        }

        $this->address = Address::create([
            'contact_id' => $data['contact_id'],
            'address_type_id' => $this->valueOrNull($data, 'address_type_id'),
            'line_1' => $this->valueOrNull($data, 'line_1'),
            'line_2' => $this->valueOrNull($data, 'line_2'),
            'city' => $this->valueOrNull($data, 'city'),
            'province' => $this->valueOrNull($data, 'province'),
            'postal_code' => $this->valueOrNull($data, 'postal_code'),
            'country' => $this->valueOrNull($data, 'country'),
            'latitude' => $this->valueOrNull($data, 'latitude'),
            'longitude' => $this->valueOrNull($data, 'longitude'),
            'lived_from_at' => $this->valueOrNull($data, 'lived_from_at'),
            'lived_until_at' => $this->valueOrNull($data, 'lived_until_at'),
            'is_past_address' => $this->valueOrFalse($data, 'is_past_address'),
        ]);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->geocodeAddress();

        $this->createFeedItem();

        return $this->address;
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
            'action' => ContactFeedItem::ACTION_CONTACT_ADDRESS_CREATED,
            'description' => MapHelper::getAddressAsString($this->address),
        ]);

        $this->address->feedItem()->save($feedItem);
    }
}
