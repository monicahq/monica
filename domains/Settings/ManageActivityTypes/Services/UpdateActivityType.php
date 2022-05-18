<?php

namespace App\Settings\ManageActivityTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\User;
use App\Services\BaseService;

class UpdateActivityType extends BaseService implements ServiceInterface
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
            'activity_type_id' => 'required|integer|exists:activity_types,id',
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
     * Update an activity type.
     *
     * @param  array  $data
     * @return ActivityType
     */
    public function execute(array $data): ActivityType
    {
        $this->validateRules($data);

        $type = ActivityType::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_type_id']);

        $type->label = $data['label'];
        $type->save();

        return $type;
    }
}
