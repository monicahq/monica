<?php

namespace App\Services\Group;

use App\Models\User;
use App\Models\Vault;
use App\Models\GroupType;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;
use PHPUnit\TextUI\XmlConfiguration\Group;

class UpdateGroupType extends BaseService implements ServiceInterface
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
            'group_type_id' => 'required|integer|exists:group_types,id',
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
            'action' => 'vault_updated',
            'objects' => json_encode([
                'vault_name' => $this->vault->name,
            ]),
        ];
    }

    /**
     * Update a group type.
     *
     * @param array $data
     * @return GroupType
     */
    public function execute(array $data): GroupType
    {
        $this->data = $data;
        $this->validate();

        $this->groupType->name = $data['name'];
        $this->groupType->description = $this->valueOrNull($data, 'description');
        $this->groupType->save();

        $this->logActionInAuditLog();

        return $this->groupType;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->author = $this->validateAuthorBelongsToAccount($this->data);

        $this->groupType = GroupType::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['group_type_id']);

        $this->validateUserPermissionInVault($this->author, $this->vault, Vault::PERMISSION_MANAGE);
    }
}
