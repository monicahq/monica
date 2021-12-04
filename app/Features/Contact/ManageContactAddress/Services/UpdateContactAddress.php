<?php

namespace App\Features\Contact\ManageContactAddress\Services;

use App\Models\User;
use App\Models\Contact;
use App\Models\AddressType;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Jobs\CreateContactLog;
use App\Models\ContactAddress;
use App\Interfaces\ServiceInterface;

class UpdateContactAddress extends BaseService implements ServiceInterface
{
    private ContactAddress $contactAddress;
    private AddressType $addressType;

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
            'address_type_id' => 'required|integer|exists:address_types,id',
            'contact_address_id' => 'required|integer|exists:contact_addresses,id',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:3',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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
     * @param array $data
     * @return ContactAddress
     */
    public function execute(array $data): ContactAddress
    {
        $this->validateRules($data);

        $this->addressType = AddressType::where('account_id', $data['account_id'])
            ->findOrFail($data['address_type_id']);

        $this->contactAddress = ContactAddress::where('contact_id', $this->contact->id)
            ->where('address_type_id', $data['address_type_id'])
            ->findOrFail($data['contact_address_id']);

        $place = $this->contactAddress->place;
        $place->street = $this->valueOrNull($data, 'street');
        $place->city = $this->valueOrNull($data, 'city');
        $place->province = $this->valueOrNull($data, 'province');
        $place->postal_code = $this->valueOrNull($data, 'postal_code');
        $place->country = $this->valueOrNull($data, 'country');
        $place->latitude = $this->valueOrNull($data, 'latitude');
        $place->longitude = $this->valueOrNull($data, 'longitude');
        $place->save();

        $this->log();

        return $this->contactAddress;
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_address_updated',
            'objects' => json_encode([
                'contact_id' => $this->contact->id,
                'contact_name' => $this->contact->name,
                'address_type_name' => $this->addressType->name,
            ]),
        ]);

        CreateContactLog::dispatch([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'contact_address_updated',
            'objects' => json_encode([
                'address_type_name' => $this->addressType->name,
            ]),
        ]);
    }
}
