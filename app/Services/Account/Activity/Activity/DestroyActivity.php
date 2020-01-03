<?php

namespace App\Services\Account\Activity\Activity;

use App\Services\BaseService;
use App\Models\Account\Activity;

class DestroyActivity extends BaseService
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
            'activity_id' => 'required|integer|exists:activities,id',
        ];
    }

    /**
     * Destroy an activity.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $activity = Activity::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_id']);

        $activity->deleteJournalEntry();

        $activity->delete();

        return true;
    }
}
