<?php

namespace App\Domains\Contact\ManageRelationships\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Gender;
use App\Models\Pronoun;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;

class ContactRelationshipsCreateViewHelper
{
    public static function data(Vault $vault, Contact $contact, User $user): array
    {
        $account = $vault->account;

        $genders = $account->genders()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Gender $gender) => [
                'id' => $gender->id,
                'name' => $gender->name,
            ]);

        $pronouns = $account->pronouns()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Pronoun $pronoun) => [
                'id' => $pronoun->id,
                'name' => $pronoun->name,
            ]);

        $groups = $account->relationshipGroupTypes()
            ->with('types')
            ->get();

        $relationshipTypeGroupsCollection = $groups
            ->map(fn (RelationshipGroupType $relationshipTypeGroup) => [
                'id' => $relationshipTypeGroup->id,
                'name' => $relationshipTypeGroup->name,
                'types' => $relationshipTypeGroup->types()->get()->map(fn (RelationshipType $relationshipType) => [
                    'id' => $relationshipType->id,
                    'name' => $relationshipType->name.' ↔ '.$relationshipType->name_reverse_relationship,
                ]),
            ]);

        $ids = $groups->pluck('id')->toArray();
        $relationshipTypesCollection = RelationshipType::whereIn('relationship_group_type_id', $ids)
            ->get()
            ->map(fn (RelationshipType $relationshipType) => [
                'id' => $relationshipType->id,
                'name' => $relationshipType->name,
                'name_reverse_relationship' => $relationshipType->name_reverse_relationship,
            ]);

        return [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->avatar,
            ],
            'genders' => $genders,
            'pronouns' => $pronouns,
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
