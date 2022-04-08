<?php

namespace App\Settings\ManageAddressTypes\Web\ViewHelpers;

use App\Models\Account;
use App\Models\AddressType;

class PersonalizeAddressTypeIndexViewHelper
{
    public static function data(Account $account): array
    {
        $addressTypes = $account->addressTypes()
            ->orderBy('name', 'asc')
            ->get();

        $collection = collect();
        foreach ($addressTypes as $addressType) {
            $collection->push(self::dtoAddressType($addressType));
        }

        return [
            'address_types' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'address_type_store' => route('settings.personalize.address_type.store'),
            ],
        ];
    }

    public static function dtoAddressType(AddressType $addressType): array
    {
        return [
            'id' => $addressType->id,
            'name' => $addressType->name,
            'url' => [
                'update' => route('settings.personalize.address_type.update', [
                    'addressType' => $addressType->id,
                ]),
                'destroy' => route('settings.personalize.address_type.destroy', [
                    'addressType' => $addressType->id,
                ]),
            ],
        ];
    }
}
