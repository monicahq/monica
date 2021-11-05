<?php

namespace App\Services\Account\Activity\Activity;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use App\Models\Journal\JournalEntry;
use App\Models\Instance\Emotion\Emotion;

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
            'activity_id' => 'required|integer|exists:activities,id',
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
     * @param  array  $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        parent::validate($data);

        Activity::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_id']);

        foreach ($data['contacts'] as $contactId) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);
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
     * Update an activity.
     *
     * @param  array  $data
     * @return Activity
     */
    public function execute(array $data): Activity
    {
        $this->validate($data);

        /** @var Activity */
        $activity = Activity::find($data['activity_id']);

        $this->update($data, $activity);

        // Log a journal entry but need to delete the previous one first
        $activity->deleteJournalEntry();
        JournalEntry::add($activity);

        // Now we update the activity with each one of the attendees
        app(AttachContactToActivity::class)->execute([
            'account_id' => $data['account_id'],
            'activity_id' => $data['activity_id'],
            'contacts' => $data['contacts'],
        ]);

        return $activity->refresh();
    }

    /**
     * Update the activity.
     *
     * @param  array  $data
     * @param  Activity  $activity
     * @return void
     */
    private function update(array $data, Activity $activity)
    {
        $activity->update([
            'activity_type_id' => $this->nullOrValue($data, 'activity_type_id'),
            'summary' => $data['summary'],
            'description' => $this->nullOrValue($data, 'description'),
            'happened_at' => $data['happened_at'],
        ]);

        if (! empty($data['emotions']) && $data['emotions'] != '') {
            $this->updateEmotions($data['emotions'], $activity);
        }
    }

    /**
     * Update activity's emotions.
     *
     * @param  array  $emotions
     * @param  Activity  $activity
     * @return void
     */
    private function updateEmotions(array $emotions, Activity $activity)
    {
        $emotionsSync = [];
        foreach ($emotions as $emotion) {
            $emotionsSync[$emotion] = ['account_id' => $activity->account_id];
        }

        $activity->emotions()->sync($emotionsSync);
    }
}
