<?php

namespace App\Services\Contact\Relationship;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;

class CreateRelationship extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'other_contact_id' => 'required|integer|exists:contacts,id',
            'relationship_type_id' => 'required|integer|exists:relationship_types,id',
        ];
    }

    /**
     * Set a relationship between two contacts.
     *
     * @param array $data
     *
     * @return Relationship
     */
    public function execute(array $data) : Relationship
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $otherContact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['other_contact_id']);

        $relationshipType = RelationshipType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_type_id']);

        // create the relationship
        return $this->setRelationship($contact, $otherContact, $relationshipType);
    }

    /**
     * Set a relationship between two contacts.
     *
     * @param Contact $contact
     * @param Contact $otherContact
     * @param RelationshipType $relationshipType
     *
     * @return Relationship
     */
    public function setRelationship(Contact $contact, Contact $otherContact, RelationshipType $relationshipType) : Relationship
    {
        // Contact A is linked to Contact B
        $relationship = Relationship::create([
            'account_id' => $relationshipType->account_id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $otherContact->id,
        ]);

        // Get the reverse relationship
        $reverseRelationshipType = $contact->account->getRelationshipTypeByType($relationshipType->name_reverse_relationship);

        if ($reverseRelationshipType) {
            // Contact B is linked to Contact A
            Relationship::create([
                'account_id' => $relationshipType->account_id,
                'relationship_type_id' => $reverseRelationshipType->id,
                'contact_is' => $otherContact->id,
                'of_contact' => $contact->id,
            ]);
        }

        return $relationship;
    }
}
