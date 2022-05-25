<?php

namespace App\Contact\ManageRelationships\Web\ViewHelpers;

use App\Helpers\AvatarHelper;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ModuleRelationshipViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        // We will build a collection of relationship group types
        // Structure will be as follow:
        // - Group type
        //    -> Relationship type
        //        -> Contact

        $numberOfDefinedRelationships = 0;
        $relationshipGroupTypes = $contact->vault->account->relationshipGroupTypes()
            ->with('types')
            ->get();

        $relationshipGroupTypesCollection = collect();
        foreach ($relationshipGroupTypes as $groupType) {
            $relationshipTypes = $groupType->types;

            $relationshipTypesCollection = collect();
            foreach ($relationshipTypes as $relationshipType) {
                $relations = DB::table('relationships')
                    ->join('contacts', 'relationships.contact_id', '=', 'contacts.id')
                    ->join('relationship_types', 'relationships.relationship_type_id', '=', 'relationship_types.id')
                    ->select('relationships.id as main_id', 'relationship_types.id', 'relationships.contact_id', 'relationships.related_contact_id')
                    ->where('relationships.relationship_type_id', $relationshipType->id)
                    ->where(function ($query) use ($contact) {
                        $query->where('relationships.contact_id', $contact->id)
                            ->orWhere('relationships.related_contact_id', $contact->id);
                    })
                    ->get();

                if ($relations->count() == 0) {
                    continue;
                }

                foreach ($relations as $relation) {
                    if ($relation->contact_id == $contact->id) {
                        $relatedContact = Contact::find($relation->related_contact_id);
                        $relationshipName = $relationshipType->name_reverse_relationship;
                    } else {
                        $relatedContact = Contact::find($relation->contact_id);
                        $relationshipName = $relationshipType->name;
                    }

                    $relationshipTypesCollection->push([
                        'contact' => self::getContact($relatedContact, $user),
                        'relationship_type' => [
                            'id' => $relationshipType->id,
                            'name' => $relationshipName,
                        ],
                        'url' => [
                            'update' => route('contact.relationships.update', [
                                'vault' => $contact->vault->id,
                                'contact' => $contact->id,
                                'relationship' => $relation->main_id,
                            ]),
                        ],
                    ]);

                    $numberOfDefinedRelationships++;
                }
            }

            $relationshipGroupTypesCollection->push([
                'id' => $groupType->id,
                'name' => $groupType->name,
                'relationship_types' => $relationshipTypesCollection,
            ]);
        }

        return [
            'relationship_group_types' => $relationshipGroupTypesCollection,
            'number_of_defined_relations' => $numberOfDefinedRelationships,
            'url' => [
                'create' => route('contact.relationships.create', [
                    'vault' => $contact->vault->id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    private static function getContact(Contact $contact, User $user): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'avatar' => AvatarHelper::getSVG($contact),
            'age' => $contact->getAge(),
            'url' => [
                'show' => $contact->listed ? route('contact.show', [
                    'vault' => $contact->vault->id,
                    'contact' => $contact->id,
                ]) : null,
            ],
        ];
    }
}
