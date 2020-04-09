<?php

namespace App\Services\Account\Activity\ActivityType;

use App\Services\BaseService;
use App\Models\Account\ActivityType;
use App\Models\Account\ActivityTypeCategory;

class CreateActivityType extends BaseService
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
            'activity_type_category_id' => 'required|integer|exists:activity_type_categories,id',
            'name' => 'nullable|string|max:255',
            'translation_key' => 'nullable|string|max:255',
        ];
    }

    /**
     * Create an activity type.
     *
     * @param array $data
     * @return ActivityType
     */
    public function execute(array $data): ActivityType
    {
        $this->validate($data);

        ActivityTypeCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_type_category_id']);

        $activityType = ActivityType::create([
            'account_id' => $data['account_id'],
            'activity_type_category_id' => $data['activity_type_category_id'],
            'name' => $this->nullOrValue($data, 'name'),
            'translation_key' => $this->nullOrValue($data, 'translation_key'),
        ]);

        return ActivityType::find($activityType->id);
    }
}
