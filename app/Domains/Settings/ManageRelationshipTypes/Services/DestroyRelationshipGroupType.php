<?php

namespace App\Domains\Settings\ManageRelationshipTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyRelationshipGroupType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'relationship_group_type_id' => 'required|integer|exists:relationship_group_types,id',
            'author_id' => 'required|uuid|exists:users,id',
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
     * Destroy a relationship group type.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = $this->account()->relationshipGroupTypes()
            ->where('can_be_deleted', true)
            ->findOrFail($data['relationship_group_type_id']);

        $type->delete();
    }
}
