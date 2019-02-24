<?php

namespace App\Services\Contact\Relationship;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;

class UpdateRelationship extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'relationship_id' => 'required|integer|exists:relationships,id',
            'relationship_type_id' => 'required|integer|exists:relationship_types,id',
        ];
    }

    /**
     * Update the relationship between two contacts.
     *
     * @param array $data
     * @return Relationship
     */
    public function execute(array $data) : Relationship
    {
        $this->validate($data);

        $relationship = Relationship::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_id']);

        $reverseRelationshipType = $relationship->account->getRelationshipTypeByType($relationship->relationshipType->name_reverse_relationship);

        $otherRelationship = Relationship::where([
                'account_id' => $data['account_id'],
                'contact_is' => $relationship->of_contact,
                'of_contact' => $relationship->contact_is,
                'relationship_type_id' => $reverseRelationshipType->id,
                ])
            ->first();

        $relationshipType = RelationshipType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_type_id']);

        // update the relationship
        $this->updateRelationship($relationship, $otherRelationship, $relationshipType);

        return $relationship;
    }

    /**
     * Update the relationship between two contacts.
     *
     * @param Relationship $relationship
     * @param Relationship|null $otherRelationship
     * @param RelationshipType $relationshipType
     */
    public function updateRelationship(Relationship $relationship, $otherRelationship, RelationshipType $relationshipType)
    {
        // Contact A is linked to Contact B
        $relationship->update([
            'relationship_type_id' => $relationshipType->id,
        ]);

        // Get the reverse relationship
        $reverseRelationshipType = $relationship->account->getRelationshipTypeByType($relationshipType->name_reverse_relationship);

        if ($otherRelationship) {
            // Contact B is linked to Contact A
            $otherRelationship->update([
                'relationship_type_id' => $reverseRelationshipType->id,
            ]);
        } else {
            Relationship::create([
                'account_id' => $relationshipType->account_id,
                'relationship_type_id' => $reverseRelationshipType->id,
                'contact_is' => $relationship->of_contact,
                'of_contact' => $relationship->contact_is,
            ]);
        }
    }
}
