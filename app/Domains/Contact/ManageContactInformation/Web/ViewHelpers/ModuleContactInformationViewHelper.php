<?php

namespace App\Domains\Contact\ManageContactInformation\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\User;

class ModuleContactInformationViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $infos = $contact->contactInformations()
            ->with('contactInformationType')
            ->get()
            ->map(fn (ContactInformation $info) => self::dto($contact, $info));

        $infoTypes = $user->account
            ->contactInformationTypes()
            ->get()
            ->map(fn (ContactInformationType $contactInformationType) => [
                'id' => $contactInformationType->id,
                'name' => $contactInformationType->name,
            ]);

        return [
            'contact_information' => $infos,
            'contact_information_types' => $infoTypes,
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
            'protocol' => $info->contactInformationType->protocol,
            'data' => $info->data,
            'data_with_protocol' => $info->contactInformationType->protocol ? $info->contactInformationType->protocol.$info->data : $info->data,
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
