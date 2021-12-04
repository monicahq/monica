<?php

namespace App\Services\Group;

use App\Models\User;
use App\Models\GroupType;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;

class CreateGroupType extends BaseService implements ServiceInterface
{
    private User $author;
    private array $data;
    private GroupType $groupType;

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
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Get the data to log after calling the service.
     *
     * @return array
     */
    public function logs(): array
    {
        return [
            'account_id' => $this->data['account_id'],
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action' => 'group_created',
            'objects' => json_encode([
                'group_type_id' => $this->groupType->id,
                'group_type_name' => $this->groupType->name,
            ]),
        ];
    }

    /**
     * Create a group type.
     *
     * @param array $data
     * @return GroupType
     */
    public function execute(array $data): GroupType
    {
        $this->data = $data;
        $this->validateRules($data);
        $this->author = $this->validateAuthorBelongsToAccount($data);

        $this->groupType = GroupType::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);

        $this->logActionInAuditLog();

        return $this->groupType;
    }
}
