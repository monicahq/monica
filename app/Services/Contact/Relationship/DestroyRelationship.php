<?php

namespace App\Services\Contact\Relationship;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;

class DestroyRelationship extends BaseService
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
        ];
    }

    /**
     * Destroy a relationship.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $relationship = Relationship::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_id']);
        $otherContact = $relationship->ofContact;

        $reverseRelationshipType = $relationship->account->getRelationshipTypeByType($relationship->relationshipType->name_reverse_relationship);

        if ($reverseRelationshipType) {
            $otherRelationship = Relationship::where([
                    'account_id' => $data['account_id'],
                    'contact_is' => $relationship->of_contact,
                    'of_contact' => $relationship->contact_is,
                    'relationship_type_id' => $reverseRelationshipType->id,
                ])
                ->first();
        } else {
            $otherRelationship = null;
        }

        $this->deleteRelationship($relationship, $otherRelationship, $otherContact);

        return true;
    }

    /**
     * Delete relationship.
     *
     * @param Relationship $relationship
     * @param Relationship|null $otherRelationship
     * @param Contact $otherContact
     */
    private function deleteRelationship(Relationship $relationship, $otherRelationship, Contact $otherContact)
    {
        if ($otherRelationship) {
            $otherRelationship->delete();
        }

        $relationship->delete();

        // the contact is partial - if the relationship is deleted, the partial
        // contact has no reason to exist anymore
        if ($otherContact->is_partial) {
            $otherContact->deleteEverything();
        }
    }
}
