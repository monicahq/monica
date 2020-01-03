<?php

namespace App\Services\Account\Activity\Activity;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;

class AttachContactToActivity extends BaseService
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
            'contacts' => 'required|array',
        ];
    }

    /**
     * Attach contacts to an activity.
     *
     * @param array $data
     * @return Activity
     */
    public function execute(array $data): Activity
    {
        $this->validate($data);

        $activity = Activity::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_id']);

        $this->attach($data, $activity);

        return $activity;
    }

    /**
     * Create the association.
     *
     * @param array $data
     * @param Activity $activity
     * @return void
     */
    private function attach(array $data, Activity $activity)
    {
        // reset current associations
        $activity->contacts()->sync([]);

        foreach ($data['contacts'] as $contactId) {
            $contact = Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);

            $activity->contacts()->syncWithoutDetaching([$contactId => [
                'account_id' => $activity->account_id,
            ]]);

            $contact->calculateActivitiesStatistics();
        }
    }
}
