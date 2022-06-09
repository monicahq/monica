<?php

namespace App\Contact\ManageContactAddresses\Web\ViewHelpers;

use App\Helpers\MapHelper;
use App\Models\Address;
use App\Models\Contact;
use App\Models\User;

class ModuleContactAddressesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $activeAddressesCollection = $contact->addresses()->where('is_past_address', false)->get();
        $inactiveAddressesCollection = $contact->addresses()->where('is_past_address', true)->get();

        // get collections
        $activeAddressesCollection = $activeAddressesCollection->map(function ($address) use ($contact, $user) {
            return self::dto($contact, $address, $user);
        });
        $inactiveAddressesCollection = $inactiveAddressesCollection->map(function ($address) use ($contact, $user) {
            return self::dto($contact, $address, $user);
        });

        $addressTypes = $contact->vault->account
            ->addressTypes()
            ->get();

        $addressTypesCollection = collect();
        foreach ($addressTypes as $addressType) {
            $addressTypesCollection->push([
                'id' => $addressType->id,
                'name' => $addressType->name,
                'selected' => false,
            ]);
        }

        return [
            'active_addresses' => $activeAddressesCollection,
            'inactive_addresses' => $inactiveAddressesCollection,
            'address_types' => $addressTypesCollection,
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
            'is_past_address' => $address->is_past_address,
            'street' => $address->street,
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
