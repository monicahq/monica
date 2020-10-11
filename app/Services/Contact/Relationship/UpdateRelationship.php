<?php

namespace App\Services\Contact\Relationship;

use App\Services\BaseService;
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
     * Update a relationship.
     *
     * @param array $data
     * @return Relationship
     */
    public function execute(array $data): Relationship
    {
        $this->validate($data);

        $relationship = Relationship::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_id']);

        $newRelationshipType = RelationshipType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_type_id']);

        $reverseRelationship = $relationship->reverseRelationship();
        if ($reverseRelationship) {
            $newReverseRelationshipType = $newRelationshipType->reverseRelationshipType();
            if ($newReverseRelationshipType) {
                $this->updateRelationship($reverseRelationship, $newReverseRelationshipType);
            }
        }

        return $this->updateRelationship($relationship, $newRelationshipType);
    }

    /**
     * Update one relationship.
     *
     * @param Relationship $relationship
     * @param RelationshipType $relationshipType
     * @return Relationship
     */
    private function updateRelationship(Relationship $relationship, RelationshipType $relationshipType): Relationship
    {
        $relationship->update([
            'relationship_type_id' => $relationshipType->id,
        ]);

        return $relationship;
    }
}
