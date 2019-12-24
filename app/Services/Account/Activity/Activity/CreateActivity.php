<?php

namespace App\Services\Account\Activity\Activity;

use App\Services\BaseService;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use App\Models\Journal\JournalEntry;
use App\Models\Instance\Emotion\Emotion;

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
            'date' => 'required|date|date_format:Y-m-d',
            'emotions' => 'nullable|array',
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

        if (! empty($data['emotions'])) {
            if ($data['emotions'] != '') {
                $this->addEmotions($data['emotions'], $activity);
            }
        }

        // Log a journal entry
        (new JournalEntry)->add($activity);

        return $activity;
    }

    /**
     * Add emotions to the activity.
     *
     * @param array $emotions
     * @param Activity $activity
     * @return void
     */
    private function addEmotions(array $emotions, Activity $activity)
    {
        foreach ($emotions as $emotionId) {
            $emotion = Emotion::findOrFail($emotionId);
            $activity->emotions()->syncWithoutDetaching([$emotion->id => [
                'account_id' => $activity->account_id,
            ]]);
        }
    }
}
