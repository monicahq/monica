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
     * Create a relationship.
     *
     * @param array $data
     * @return Relationship
     */
    public function execute(array $data) : Relationship
    {
        $this->validate($data);

        $relationshipType = RelationshipType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_type_id']);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_is']);
        $partner = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['of_contact']);

        $this->setRelationship($partner, $contact, $relationshipType->reverseRelationshipType());
        return $this->setRelationship($contact, $partner, $relationshipType);
    }

    /**
     * Set a relationship between two contacts.
     *
     * @param Contact $contact
     * @param Contact $otherContact
     * @param RelationshipType $relationshipType
     * @return Relationship
     */
    private function setRelationship(Contact $contact, Contact $otherContact, RelationshipType $relationshipType) : Relationship
    {
        $relationship = new Relationship;
        $relationship->account_id = $relationshipType->account_id;
        $relationship->relationship_type_id = $relationshipType->id;
        $relationship->contact_is = $contact->id;
        $relationship->relationship_type_name = $relationshipType->name;
        $relationship->of_contact = $otherContact->id;
        $relationship->save();

        return $relationship;
    }
}
