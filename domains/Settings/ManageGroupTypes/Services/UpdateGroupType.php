<?php

namespace App\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Models\User;
use App\Services\BaseService;

class UpdateGroupType extends BaseService implements ServiceInterface
{
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
            'label' => 'required|string|max:255',
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
     * Update a group type.
     *
     * @param  array  $data
     * @return GroupType
     */
    public function execute(array $data): GroupType
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->groupType;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->groupType = GroupType::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['group_type_id']);
    }

    private function update(): void
    {
        $this->groupType->label = $this->data['label'];
        $this->groupType->save();
    }
}
