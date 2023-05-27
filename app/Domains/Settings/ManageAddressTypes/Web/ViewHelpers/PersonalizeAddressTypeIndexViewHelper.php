<?php

namespace App\Domains\Settings\ManageAddressTypes\Web\ViewHelpers;

use App\Models\Account;
use App\Models\AddressType;

class PersonalizeAddressTypeIndexViewHelper
{
    public static function data(Account $account): array
    {
        $addresses = $account->addressTypes()
            ->get()
            ->sortByCollator('name')
            ->map(fn (AddressType $addressType) => self::dtoAddressType($addressType));

        return [
            'address_types' => $addresses,
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
