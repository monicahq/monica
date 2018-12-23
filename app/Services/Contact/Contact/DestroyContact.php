<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Services\Contact\Relationship\DestroyRelationship;

class DestroyContact extends BaseService
{
    private $contact;

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
        ];
    }

    /**
     * Destroy a contact.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $this->contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $this->destroyRelationships($data);

        $this->contact->deleteAvatars();
        $this->contact->deleteEverything();

        return true;
    }

    /**
     * Destroy all associated relationships.
     *
     * @param array $data
     * @return void
     */
    private function destroyRelationships(array $data)
    {
        $relationships = Relationship::where('contact_is', $this->contact->id)->get();
        $this->destroySpecificRelationships($data, $relationships);

        $relationships = Relationship::where('of_contact', $this->contact->id)->get();
        $this->destroySpecificRelationships($data, $relationships);
    }

    /**
     * Delete specific relationships.
     *
     * @param array $data
     * @param $relationships
     * @return void
     */
    private function destroySpecificRelationships(array $data, $relationships)
    {
        foreach ($relationships as $relationship) {
            $data = [
                'account_id' => $data['account_id'],
                'relationship_id' => $relationship->id,
            ];

            $relationshipService = new DestroyRelationship;
            $relationshipService->execute($data);
        }
    }
}
