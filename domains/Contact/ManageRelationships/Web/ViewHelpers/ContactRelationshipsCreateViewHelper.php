<?php

namespace App\Contact\ManageRelationships\Web\ViewHelpers;

use App\Helpers\AvatarHelper;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;

class ContactRelationshipsCreateViewHelper
{
    public static function data(Vault $vault, Contact $contact, User $user): array
    {
        $account = $vault->account;

        $genders = $account->genders()->orderBy('name', 'asc')->get();
        $genderCollection = $genders->map(function ($gender) {
            return [
                'id' => $gender->id,
                'name' => $gender->name,
            ];
        });

        $pronouns = $account->pronouns()->orderBy('name', 'asc')->get();
        $pronounCollection = $pronouns->map(function ($pronoun) {
            return [
                'id' => $pronoun->id,
                'name' => $pronoun->name,
            ];
        });

        $groups = $account->relationshipGroupTypes()
            ->with('types')
            ->get();

        $relationshipTypeGroupsCollection = $groups
            ->map(function ($relationshipTypeGroup) {
                return [
                    'id' => $relationshipTypeGroup->id,
                    'name' => $relationshipTypeGroup->name,
                    'types' => $relationshipTypeGroup->types()->get()->map(function ($relationshipType) {
                        return [
                            'id' => $relationshipType->id,
                            'name' => $relationshipType->name.' ↔ '.$relationshipType->name_reverse_relationship,
                        ];
                    }),
                ];
            });

        $ids = $groups->pluck('id')->toArray();
        $relationshipTypesCollection = RelationshipType::whereIn('relationship_group_type_id', $ids)
            ->get()
            ->map(function ($relationshipType) {
                return [
                    'id' => $relationshipType->id,
                    'name' => $relationshipType->name,
                    'name_reverse_relationship' => $relationshipType->name_reverse_relationship,
                ];
            });

        return [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->getName($user),
                'avatar' => AvatarHelper::getSVG($contact),
            ],
            'genders' => $genderCollection,
            'pronouns' => $pronounCollection,
            'relationship_group_types' => $relationshipTypeGroupsCollection,
            'relationship_types' => $relationshipTypesCollection,
            'url' => [
                'store' => route('contact.relationships.store', [
                    'vault' => $vault->id,
                    'contact' => $contact->id,
                ]),
                'back' => route('contact.show', [
                    'vault' => $vault->id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
