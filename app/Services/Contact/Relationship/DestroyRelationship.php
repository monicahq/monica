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
            ->where('id', $data['relationship_id'])
            ->firstOrFail();

        $relationship->delete();

        return true;
    }
}
