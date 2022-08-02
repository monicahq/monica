<?php

namespace App\Contact\ManageGroups\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Group;
use App\Models\GroupType;
use App\Services\BaseService;

class CreateGroup extends BaseService implements ServiceInterface
{
    private Group $group;

    private array $array;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'group_type_id' => 'required|integer|exists:group_types,id',
            'name' => 'nullable|string|max:255',
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
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Create a family.
     *
     * @param  array  $data
     * @return Group
     */
    public function execute(array $data): Group
    {
        $this->data = $data;
        $this->validate();

        $this->group = Group::create([
            'group_type_id' => $data['group_type_id'],
            'vault_id' => $data['vault_id'],
            'name' => $this->valueOrNull($data, 'name'),
        ]);

        return $this->group;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        GroupType::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['group_type_id']);
    }
}
