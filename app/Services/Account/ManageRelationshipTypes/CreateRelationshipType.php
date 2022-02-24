<?php

namespace App\Services\Account\ManageRelationshipTypes;

use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Models\RelationshipType;
use App\Interfaces\ServiceInterface;
use App\Models\RelationshipGroupType;

class CreateRelationshipType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'relationship_group_type_id' => 'required|integer|exists:relationship_group_types,id',
            'name' => 'required|string|max:255',
            'name_reverse_relationship' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     *
     * @param  array  $data
     * @return RelationshipType
     */
    public function execute(array $data): RelationshipType
    {
        $this->validateRules($data);

        $group = RelationshipGroupType::where('account_id', $data['account_id'])
            ->findOrFail($data['relationship_group_type_id']);

        $type = RelationshipType::create([
            'relationship_group_type_id' => $group->id,
            'name' => $data['name'],
            'name_reverse_relationship' => $data['name_reverse_relationship'],
        ]);

        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'relationship_type_created',
            'objects' => json_encode([
                'name' => $type->name,
                'group_type_name' => $group->name,
            ]),
        ])->onQueue('low');

        return $type;
    }
}
