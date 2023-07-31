<?php

namespace App\Domains\Contact\ManageRelationships\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ModuleFamilySummaryViewHelper
{
    /**
     * Get a summary of the family of the contact:
     * - spouse or partner
     * - children.
     */
    public static function data(Contact $contact, User $user): array
    {
        $loveRelationshipType = $contact->vault->account->relationshipGroupTypes()
            ->firstWhere('type', RelationshipGroupType::TYPE_LOVE);

        $loveRelationships = $loveRelationshipType->types()
            ->where('type', RelationshipType::TYPE_LOVE)
            ->get();

        $loveRelationshipsCollection = self::getRelations($loveRelationships, $contact);

        $familyRelationshipType = $contact->vault->account->relationshipGroupTypes()
            ->firstWhere('type', RelationshipGroupType::TYPE_FAMILY);

        $familyRelationships = $familyRelationshipType->types()
            ->where('type', RelationshipType::TYPE_CHILD)
            ->get();

        $familyRelationshipsCollection = self::getRelations($familyRelationships, $contact);

        return [
            'family_relationships' => $familyRelationshipsCollection,
            'love_relationships' => $loveRelationshipsCollection,
        ];
    }

    private static function getRelations(EloquentCollection $collection, Contact $contact): Collection
    {
        $relationshipsCollection = collect();
        $counter = 0;
        foreach ($collection as $relationshipType) {
            $relations = DB::table('relationships')
                ->join('contacts', 'relationships.contact_id', '=', 'contacts.id')
                ->join('relationship_types', 'relationships.relationship_type_id', '=', 'relationship_types.id')
                ->select('relationships.id as main_id', 'relationship_types.id', 'relationships.contact_id', 'relationships.related_contact_id')
                ->where('relationships.relationship_type_id', $relationshipType->getKey())
                ->where(function ($query) use ($contact) {
                    $query->where('relationships.contact_id', $contact->id)
                        ->orWhere('relationships.related_contact_id', $contact->id);
                })
                ->get();

            if ($relations->count() === 0) {
                continue;
            }

            foreach ($relations as $relation) {
                if ($relation->contact_id === $contact->id) {
                    $relatedContact = Contact::find($relation->related_contact_id);
                } else {
                    $relatedContact = Contact::find($relation->contact_id);
                }

                if ($relatedContact === null) {
                    continue;
                }

                $relationshipsCollection->push([
                    'id' => $counter,
                    'contact' => self::getContact($relatedContact),
                ]);
            }
            $counter++;
        }

        return $relationshipsCollection;
    }

    private static function getContact(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'avatar' => $contact->avatar,
            'age' => $contact->age,
            'url' => [
                'show' => $contact->listed ? route('contact.show', [
                    'vault' => $contact->vault->id,
                    'contact' => $contact->id,
                ]) : null,
            ],
        ];
    }
}
