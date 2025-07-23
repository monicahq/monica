<?php

namespace App\Domains\Contact\ManageContactInformation\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModuleContactInformationViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $infos = $contact->contactInformations()
            ->with('contactInformationType')
            ->get()
            ->groupBy(fn (ContactInformation $info) => $info->contactInformationType->type)
            ->map(fn (Collection $collection) => $collection
                ->map(fn (ContactInformation $info) => self::dto($info))
            );
        $groups = self::infoGroups();

        $infoTypes = $user->account
            ->contactInformationTypes()
            ->get()
            ->groupBy('type')
            ->map(fn (Collection $collection) => [
                'optgroup' => $groups[$collection[0]->type],
                'options' => $collection
                    ->map(fn (ContactInformationType $contactInformationType) => [
                        'id' => $contactInformationType->id,
                        'name' => $contactInformationType->name,
                        'type' => $contactInformationType->type,
                        'name_translation_key' => $contactInformationType->name_translation_key,
                    ])
                    ->sortByCollator('name')
                    ->toArray(),
            ]
            );

        return [
            'contact_information' => $infos,
            'contact_information_types' => $infoTypes,
            'contact_information_kinds' => self::infoKinds(),
            'contact_information_groups' => self::infoGroups(),
            'protocols' => config('app.social_protocols'),
            'url' => [
                'store' => route('contact.contact_information.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    private static function infoKinds(): Collection
    {
        return collect(config('app.contact_information_kinds'))
            ->map(fn (array $list) => collect($list)
                ->map(fn (array $kind) => [
                    'id' => $kind['id'],
                    'name' => __($kind['name_translation_key']),
                ])
                ->sortByCollator('name')
                ->prepend(['id' => '', 'name' => ''])
            );
    }

    public static function infoGroups(): Collection
    {
        return collect(config('app.contact_information_groups'))
            ->map(fn (array $group) => __($group['name_translation_key']));
    }

    public static function dto(ContactInformation $info): array
    {
        $infoKinds = self::infoKinds();

        $contactInformationKind = [];
        if ($info->kind !== null) {
            if ($infoKinds->has($info->contactInformationType->type) && ($kind = $infoKinds[$info->contactInformationType->type]->firstWhere('id', Str::lower($info->kind))) !== null) {
                $contactInformationKind['id'] = $kind['id'];
                $contactInformationKind['name'] = $kind['name'] ?? '';
            } else {
                $contactInformationKind['id'] = '-1';
                $contactInformationKind['name'] = $info->kind;
            }
        }

        return [
            'id' => $info->id,
            'label' => $info->name,
            'protocol' => $info->contactInformationType->protocol,
            'data' => $info->data,
            'data_with_protocol' => $info->dataWithProtocol,
            'contact_information_type' => [
                'id' => $info->contactInformationType->id,
                'name' => $info->contactInformationType->name,
                'type' => $info->contactInformationType->type,
            ],
            'contact_information_kind' => $info->kind !== null ? $contactInformationKind : null,
            'url' => [
                'update' => route('contact.contact_information.update', [
                    'vault' => $info->contact->vault_id,
                    'contact' => $info->contact_id,
                    'info' => $info->id,
                ]),
                'destroy' => route('contact.contact_information.destroy', [
                    'vault' => $info->contact->vault_id,
                    'contact' => $info->contact_id,
                    'info' => $info->id,
                ]),
            ],
        ];
    }
}
