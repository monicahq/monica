<?php

namespace App\Services\Account\Activity\ActivityType;

use App\Services\BaseService;
use App\Models\Account\ActivityType;

class DestroyActivityType extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'activity_type_id' => 'required|integer|exists:activity_types,id',
        ];
    }

    /**
     * Destroy a activity type.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $activityType = ActivityType::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_type_id']);

        $activityType->delete();

        return true;
    }
}
