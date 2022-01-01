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
            'contact_is' => 'required|integer|exists:contacts,id',
            'of_contact' => 'required|integer|exists:contacts,id',
            'relationship_type_id' => 'required|integer|exists:relationship_types,id',
        ];
    }

    /**
     * Set a relationship between two contacts.
     *
     * @param  array  $data
     * @return Relationship
     */
    public function execute(array $data): Relationship
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_is']);

        $contact->throwInactive();

        $otherContact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['of_contact']);

        $relationshipType = RelationshipType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_type_id']);

        // create the relationship
        $relationship = $this->setRelationship($contact, $otherContact, $relationshipType);

        $reverseRelationshipType = $relationshipType->reverseRelationshipType();
        if ($reverseRelationshipType) {
            // create the reverse relationship
            $this->setRelationship($otherContact, $contact, $reverseRelationshipType);
        }

        return $relationship;
    }

    /**
     * Set a relationship between two contacts.
     *
     * @param  Contact  $contact
     * @param  Contact  $otherContact
     * @param  RelationshipType  $relationshipType
     * @return Relationship
     */
    public function setRelationship(Contact $contact, Contact $otherContact, RelationshipType $relationshipType): Relationship
    {
        return Relationship::create([
            'account_id' => $relationshipType->account_id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $otherContact->id,
        ]);
    }
}
