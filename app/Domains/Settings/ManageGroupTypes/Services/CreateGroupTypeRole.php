<?php

namespace App\Domains\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupTypeRole;
use App\Models\User;
use App\Services\BaseService;

class CreateGroupTypeRole extends BaseService implements ServiceInterface
{
    private array $data;

    private GroupTypeRole $groupTypeRole;

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
     * Create a group type role.
     *
     * @param  array  $data
     * @return GroupTypeRole
     */
    public function execute(array $data): GroupTypeRole
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->groupTypeRole;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        // determine the new position of the template page
        $newPosition = GroupTypeRole::where('group_type_id', $this->data['group_type_id'])
            ->max('position');
        $newPosition++;

        $this->groupTypeRole = GroupTypeRole::create([
            'group_type_id' => $this->data['group_type_id'],
            'label' => $this->data['label'],
            'position' => $newPosition,
        ]);
    }
}
