<?php

namespace App\Services\Account\Activity\Activity;

use App\Services\BaseService;
use App\Models\Contact\Contact;
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
            'activity_type_id' => 'nullable|integer|exists:activity_types,id',
            'summary' => 'required|string:255',
            'description' => 'nullable|string:1000000',
            'happened_at' => 'required|date|date_format:Y-m-d',
            'emotions' => 'nullable|array',
            'contacts' => 'required|array',
        ];
    }

    /**
     * Validate all datas to execute the service.
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        parent::validate($data);

        if (count($data['contacts']) > 0) {
            foreach ($data['contacts'] as $contactId) {
                Contact::where('account_id', $data['account_id'])
                    ->findOrFail($contactId);
            }
        }

        if (! empty($data['activity_type_id']) && $data['activity_type_id'] != '') {
            ActivityType::where('account_id', $data['account_id'])
                ->findOrFail($data['activity_type_id']);
        }

        if (! empty($data['emotions']) && $data['emotions'] != '') {
            foreach ($data['emotions'] as $emotionId) {
                Emotion::findOrFail($emotionId);
            }
        }

        return true;
    }

    /**
     * Create an activity.
     *
     * @param array $data
     * @return Activity
     */
    public function execute(array $data): Activity
    {
        $this->validate($data);

        $activity = $this->create($data);

        // Log a journal entry
        JournalEntry::add($activity);

        // Now we associate the activity with each one of the attendees
        app(AttachContactToActivity::class)->execute([
            'account_id' => $data['account_id'],
            'activity_id' => $activity->id,
            'contacts' => $data['contacts'],
        ]);

        return $activity;
    }

    /**
     * Create the activity.
     *
     * @param array $data
     * @return Activity
     */
    private function create(array $data): Activity
    {
        $activity = Activity::create([
            'account_id' => $data['account_id'],
            'activity_type_id' => $this->nullOrValue($data, 'activity_type_id'),
            'summary' => $data['summary'],
            'description' => $this->nullOrValue($data, 'description'),
            'happened_at' => $data['happened_at'],
        ]);

        if (! empty($data['emotions']) && $data['emotions'] != '') {
            $this->addEmotions($data['emotions'], $activity);
        }

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
        $emotionsSync = [];
        foreach ($emotions as $emotion) {
            $emotionsSync[$emotion] = ['account_id' => $activity->account_id];
        }

        $activity->emotions()->sync($emotionsSync);
    }
}
