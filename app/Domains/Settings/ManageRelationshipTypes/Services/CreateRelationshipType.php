<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\RelationshipType;
use App\Services\BaseService;

class CreateRelationshipType extends BaseService implements ServiceInterface
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
            'name' => 'required|string|max:255',
            'name_reverse_relationship' => 'required|string|max:255',
            'can_be_deleted' => 'required|boolean',
            'type' => 'nullable|string|max:255',
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
     * Create a relationship type.
     */
    public function execute(array $data): RelationshipType
    {
        $this->validateRules($data);

        $group = $this->account()->relationshipGroupTypes()
            ->findOrFail($data['relationship_group_type_id']);

        $type = RelationshipType::create([
            'relationship_group_type_id' => $group->id,
            'name' => $data['name'],
            'name_reverse_relationship' => $data['name_reverse_relationship'],
            'can_be_deleted' => $data['can_be_deleted'],
            'type' => $this->valueOrNull($data, 'type'),
        ]);

        return $type;
    }
}
