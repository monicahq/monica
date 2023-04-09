<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyRelationshipType extends BaseService implements ServiceInterface
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
     * Destroy a relationship type.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $group = $this->account()->relationshipGroupTypes()
            ->findOrFail($data['relationship_group_type_id']);

        $type = $group->types()
            ->where('can_be_deleted', true)
            ->findOrFail($data['relationship_type_id']);

        $type->delete();
    }
}
