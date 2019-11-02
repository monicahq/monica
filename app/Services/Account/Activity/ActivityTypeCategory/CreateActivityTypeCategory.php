<?php

namespace App\Services\Account\Activity\ActivityTypeCategory;

use App\Services\BaseService;
use App\Models\Account\ActivityTypeCategory;

class CreateActivityTypeCategory extends BaseService
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
            'name' => 'nullable|string|max:255',
            'translation_key' => 'nullable|string|max:255',
        ];
    }

    /**
     * Create an activity type category.
     *
     * @param array $data
     * @return ActivityTypeCategory
     */
    public function execute(array $data) : ActivityTypeCategory
    {
        $this->validate($data);

        $activityTypeCategory = ActivityTypeCategory::create([
            'account_id' => $data['account_id'],
            'name' => $this->nullOrValue($data, 'name'),
            'translation_key' => $this->nullOrValue($data, 'translation_key'),
        ]);

        return ActivityTypeCategory::find($activityTypeCategory->id);
    }
}
