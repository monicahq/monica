<?php

namespace App\Services\Account\Activity\Activity;

use App\Services\BaseService;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;

class UpdateActivity extends BaseService
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
            'activity_id' => 'required|integer|exists:activities,id',
            'summary' => 'required|string:255',
            'description' => 'nullable|string:400000000',
            'date' => 'required|date|date_format:Y-m-d',
        ];
    }

    /**
     * Update an activity.
     *
     * @param array $data
     * @return Activity
     */
    public function execute(array $data) : Activity
    {
        $this->validate($data);

        $activity = Activity::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_id']);

        ActivityType::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_type_id']);

        $activity->update([
            'activity_type_id' => $data['activity_type_id'],
            'summary' => $data['summary'],
            'description' => $this->nullOrValue($data, 'description'),
            'happened_at' => $data['date'],
        ]);

        return $activity;
    }
}
