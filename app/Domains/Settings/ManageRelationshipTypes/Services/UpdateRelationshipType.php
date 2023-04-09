<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\RelationshipType;
use App\Services\BaseService;

class UpdateRelationshipType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'relationship_group_type_id' => 'required|integer|exists:relationship_group_types,id',
            'relationship_type_id' => 'required|integer|exists:relationship_types,id',
            'name' => 'required|string|max:255',
            'name_reverse_relationship' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update a relationship type.
     */
    public function execute(array $data): RelationshipType
    {
        $this->validateRules($data);

        $group = $this->account()->relationshipGroupTypes()
            ->findOrFail($data['relationship_group_type_id']);

        $type = $group->types()
            ->findOrFail($data['relationship_type_id']);

        $type->name = $data['name'];
        $type->name_reverse_relationship = $data['name_reverse_relationship'];
        $type->save();

        return $type;
    }
}
