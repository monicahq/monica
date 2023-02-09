<?php

namespace App\Domains\Contact\ManageContactAddresses\Web\ViewHelpers;

use App\Helpers\MapHelper;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Contact;
use App\Models\User;

class ModuleContactAddressesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $contactActiveAddressesCollection = $contact->addresses()
            ->wherePivot('is_past_address', false)
            ->get()
            ->map(fn (Address $address) => self::dto($contact, $address, $user));

        $contactInactiveAddressesCollection = $contact->addresses()
            ->wherePivot('is_past_address', true)
            ->get()
            ->map(fn (Address $address) => self::dto($contact, $address, $user));

        $addressTypesCollection = $contact->vault->account
            ->addressTypes()
            ->get()
            ->map(fn (AddressType $addressType) => [
                'id' => $addressType->id,
                'name' => $addressType->name,
                'selected' => false,
            ]);

        $vaultAddressesCollection = $contact->vault->addresses()
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'address' => MapHelper::getAddressAsString($address),
            ]);

        return [
            'active_addresses' => $contactActiveAddressesCollection,
            'inactive_addresses' => $contactInactiveAddressesCollection,
            'address_types' => $addressTypesCollection,
            'addresses_in_vault' => $vaultAddressesCollection,
            'url' => [
                'store' => route('contact.address.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, Address $address, User $user): array
    {
        return [
            'id' => $address->id,
            'is_past_address' => $address->pivot ? (bool) $address->pivot->is_past_address : false,
            'line_1' => $address->line_1,
            'line_2' => $address->line_2,
            'city' => $address->city,
            'province' => $address->province,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'type' => $address->addressType ? [
                'id' => $address->addressType->id,
                'name' => $address->addressType->name,
            ] : null,
            'url' => [
                'show' => MapHelper::getMapLink($address, $user),
                'update' => route('contact.address.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'address' => $address->id,
                ]),
                'destroy' => route('contact.address.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'address' => $address->id,
                ]),
            ],
        ];
    }
}
