<?php

namespace App\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Models\User;
use App\Services\BaseService;

class CreateGroupType extends BaseService implements ServiceInterface
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
     * Create a group type.
     *
     * @param  array  $data
     * @return GroupType
     */
    public function execute(array $data): GroupType
    {
        $this->data = $data;

        $this->validate();
        $this->create();

        return $this->groupType;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        // determine the new position of the template page
        $newPosition = GroupType::where('account_id', $this->data['account_id'])
            ->max('position');
        $newPosition++;

        $this->groupType = GroupType::create([
            'account_id' => $this->data['account_id'],
            'label' => $this->data['label'],
            'position' => $newPosition,
        ]);
    }
}
