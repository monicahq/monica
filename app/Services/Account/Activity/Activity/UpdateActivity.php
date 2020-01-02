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
            'summary' => 'required|string:100000',
            'description' => 'nullable|string:400000000',
            'happened_at' => 'required|date|date_format:Y-m-d',
            'emotions' => 'nullable|array',
            'contacts' => 'required|array',
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

        foreach ($data['contacts'] as $contactId) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);
        }

        if (isset($data['activity_type_id']) && $data['activity_type_id']) {
            ActivityType::where('account_id', $data['account_id'])
                ->findOrFail($data['activity_type_id']);
        }

        if (isset($data['emotions']) && $data['emotions']) {
            foreach ($data['emotions'] as $emotionId) {
                Emotion::findOrFail($emotionId);
            }
        }

        $this->updateActivity($data, $activity);

        // Log a journal entry but need to delete the previous one first
        $activity->deleteJournalEntry();
        JournalEntry::add($activity);

        // Now we update the activity with each one of the attendees
        app(AttachContactToActivity::class)->execute([
            'account_id' => $data['account_id'],
            'activity_id' => $data['activity_id'],
            'contacts' => $data['contacts'],
        ]);

        return $activity;
    }

    /**
     * Update the activity.
     *
     * @param array $data
     * @param Activity $activity
     * @return void
     */
    private function updateActivity(array $data, Activity $activity)
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
     * @param array $emotions
     * @param Activity $activity
     * @return void
     */
    private function updateEmotions(array $emotions, Activity $activity)
    {
        // Find existing emotions
        $existing = $activity->emotions()->get();

        foreach ($existing as $emotion) {
            // Has an existing attendee been removed?
            if (! in_array($emotion->id, $emotions)) {
                $emotion->activities()->detach($activity);
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            $idx = array_search($emotion->id, $emotions);
            unset($emotions[$idx]);
        }

        foreach ($emotions as $emotionId) {
            $emotion = Emotion::findOrFail($emotionId);
            $activity->emotions()->syncWithoutDetaching([$emotion->id => [
                'account_id' => $activity->account_id,
            ]]);
        }
    }
}
