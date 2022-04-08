<?php

namespace App\Settings\ManageContactInformationTypes\Web\ViewHelpers;

use App\Models\Account;
use App\Models\ContactInformationType;

class PersonalizeContactInformationTypeIndexViewHelper
{
    public static function data(Account $account): array
    {
        $types = $account->contactInformationTypes()
            ->orderBy('name', 'asc')
            ->get();

        $collection = collect();
        foreach ($types as $type) {
            $collection->push(self::dtoContactInformationType($type));
        }

        return [
            'contact_information_types' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'contact_information_type_store' => route('settings.personalize.contact_information_type.store'),
            ],
        ];
    }

    public static function dtoContactInformationType(ContactInformationType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'protocol' => $type->protocol,
            'can_be_deleted' => $type->can_be_deleted,
            'url' => [
                'update' => route('settings.personalize.contact_information_type.update', [
                    'type' => $type->id,
                ]),
                'destroy' => route('settings.personalize.contact_information_type.destroy', [
                    'type' => $type->id,
                ]),
            ],
        ];
    }
}
