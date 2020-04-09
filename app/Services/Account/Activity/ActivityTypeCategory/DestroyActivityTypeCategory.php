<?php

namespace App\Services\Account\Activity\ActivityTypeCategory;

use App\Services\BaseService;
use App\Models\Account\ActivityTypeCategory;

class DestroyActivityTypeCategory extends BaseService
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
        ];
    }

    /**
     * Destroy a activity type category.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $activityTypeCategory = ActivityTypeCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_type_category_id']);

        $activityTypeCategory->delete();

        return true;
    }
}
