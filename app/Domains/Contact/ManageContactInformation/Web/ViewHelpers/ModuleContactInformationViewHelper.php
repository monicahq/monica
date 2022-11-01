<?php

namespace App\Domains\Contact\ManageContactInformation\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\User;

class ModuleContactInformationViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $infos = $contact->contactInformation()->with('contactInformationType')->get();

        $infosCollection = $infos->map(function ($info) use ($contact) {
            return self::dto($contact, $info);
        });

        $infoTypesCollection = $user->account
            ->contactInformationTypes()
            ->get()
            ->map(function ($contactInformationType) {
                return [
                    'id' => $contactInformationType->id,
                    'name' => $contactInformationType->name,
                ];
            });

        return [
            'contact_information' => $infosCollection,
            'contact_information_types' => $infoTypesCollection,
            'url' => [
                'store' => route('contact.contact_information.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, ContactInformation $info): array
    {
        return [
            'id' => $info->id,
            'label' => $info->name,
            'data' => $info->contactInformationType->protocol ? $info->contactInformationType->protocol.$info->data : $info->data,
            'contact_information_type' => [
                'id' => $info->contactInformationType->id,
                'name' => $info->contactInformationType->name,
            ],
            'url' => [
                'update' => route('contact.contact_information.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'info' => $info->id,
                ]),
                'destroy' => route('contact.contact_information.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'info' => $info->id,
                ]),
            ],
        ];
    }
}
