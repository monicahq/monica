<?php

namespace App\Services\Contact\Relationship;

use Illuminate\Support\Arr;
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
    public function execute(array $data) : Relationship
    {
        $this->validate($data);

        RelationshipType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_type_id']);

        $relationship = Relationship::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_id']);

        app(DestroyRelationship::class)->execute([
            Arr::only($data, [
                'account_id',
                'relationship_id',
            ])
        ]);

        return app(CreateRelationship::class)->execute([
            Arr::only($data, [
                'account_id',
                'relationship_type_id',
            ]) + [
                'contact_is' => $relationship->contact->id,
                'of_contact' => $relationship->of_contact->id,
            ]
        ]);
    }
}
