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

        return true;
    }

    /**
     * Attach contacts to an activity.
     *
     * @param  array  $data
     * @return Activity
     */
    public function execute(array $data): Activity
    {
        $this->validate($data);

        /** @var Activity */
        $activity = Activity::find($data['activity_id']);

        $this->attach($data, $activity);

        return $activity;
    }

    /**
     * Create the association.
     *
     * @param  array  $data
     * @param  Activity  $activity
     * @return void
     */
    private function attach(array $data, Activity $activity)
    {
        $attendees = [];
        foreach ($data['contacts'] as $contact) {
            $attendees[$contact] = ['account_id' => $activity->account_id];
        }

        // sync attendees: old contacts will be detached automatically
        $changes = $activity->contacts()->sync($attendees);

        foreach ($changes as $change) {
            // detached, attached, and updated attendees
            foreach ($change as $contactId) {
                Contact::find($contactId)->calculateActivitiesStatistics();
            }
        }
    }
}
