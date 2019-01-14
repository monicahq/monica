<?php

namespace App\Services\Activity\Activity;

use App\Services\BaseService;
use App\Models\Contact\Activity;
use App\Models\Contact\ActivityType;

class CreateActivity extends BaseService
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
            'activity_type_id' => 'nullable|integer',
            'summary' => 'required|string:255',
            'description' => 'nullable|string:400000000',
            'date' => 'required|date_format:Y-m-d',
        ];
    }

    /**
     * Create an activity.
     *
     * @param array $data
     * @return Activity
     */
    public function execute(array $data) : Activity
    {
        $this->validate($data);

        if ($data['activity_type_id']) {
            ActivityType::where('account_id', $data['account_id'])
                ->findOrFail($data['activity_type_id']);
        }

        $activity = Activity::create([
            'account_id' => $data['account_id'],
            'activity_type_id' => $this->nullOrValue($data, 'activity_type_id'),
            'summary' => $data['summary'],
            'description' => $this->nullOrValue($data, 'description'),
            'happened_at' => $data['date'],
        ]);

        return Activity::find($activity->id);
    }
}
