<?php

namespace App\Services\Contact\Relationship;

use App\Services\BaseService;
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

        $reverseRelationship = $relationship->reverseRelationship();
        if ($reverseRelationship) {
            $reverseRelationship->delete();
        }

        $relationship->delete();

        // the contact is partial - if the relationship is deleted, the partial
        // contact has no reason to exist anymore
        if ($otherContact->is_partial) {
            $otherContact->deleteEverything();
        }

        return true;
    }
}
