<?php

namespace App\Domains\Contact\ManageContactInformation\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\User;
use Illuminate\Support\Collection;

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
                'type' => $contactInformationType->type,
                'name_translation_key' => $contactInformationType->name_translation_key,
            ]);

        return [
            'contact_information' => $infos,
            'contact_information_types' => $infoTypes,
            'contact_information_kinds' => static::infoKinds(),
            'protocols' => config('app.social_protocols'),
            'url' => [
                'store' => route('contact.contact_information.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function infoKinds(): Collection
    {
        $infoKinds = collect([
            'email' => collect([
                [
                    'id' => 'work',
                    'name' => trans('Work'),
                ],
                [
                    'id' => 'home',
                    'name' => trans('Home House'),
                ],
                [
                    'id' => 'personal',
                    'name' => trans('Personal'),
                ],
            ]),
            'phone' => collect([
                [
                    'id' => 'work',
                    'name' => trans('Work'),
                ],
                [
                    'id' => 'home',
                    'name' => trans('Home House'),
                ],
                [
                    'id' => 'cell',
                    'name' => trans('Mobile'),
                ],
                [
                    'id' => 'text',
                    'name' => trans('Text'),
                ],
                [
                    'id' => 'fax',
                    'name' => trans('Fax'),
                ],
                [
                    'id' => 'pager',
                    'name' => trans('Pager'),
                ],
            ]),
        ]);

        return $infoKinds->map(fn ($list) => collect([['id' => '', 'name' => '']])->merge($list->sortByCollator('name')));
    }

    public static function dto(Contact $contact, ContactInformation $info): array
    {
        $infoKinds = static::infoKinds();

        return [
            'id' => $info->id,
            'label' => $info->name,
            'protocol' => $info->contactInformationType->protocol,
            'data' => $info->data,
            'data_with_protocol' => $info->dataWithProtocol,
            'contact_information_type' => [
                'id' => $info->contactInformationType->id,
                'name' => $info->contactInformationType->name,
            ],
            'contact_information_kind' => $info->kind !== null && $infoKinds->has($info->contactInformationType->type) ? [
                'id' => $info->kind,
                'name' => $infoKinds[$info->contactInformationType->type]->firstWhere('id', $info->kind)['name'],
            ] : null,
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
