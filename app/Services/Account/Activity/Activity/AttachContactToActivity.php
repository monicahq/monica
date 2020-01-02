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
    public function execute(array $data) : Activity
    {
        $this->validate($data);

        $activity = Activity::where('account_id', $data['account_id'])
            ->findOrFail($data['activity_id']);

        foreach ($data['contacts'] as $contactId) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);
        }

        $attendeesID = $this->detach($data, $activity);

        $this->attach($data, $activity, $attendeesID);

        return $activity;
    }

    /**
     * Detach the previous contacts.
     *
     * @param array $data
     * @param Activity $activity
     * @return array
     */
    private function detach(array $data, Activity $activity) : array
    {
        // Get the attendees
        $attendeesID = $data['contacts'];

        // Find existing contacts
        $existing = $activity->contacts()->get();

        foreach ($existing as $contact) {
            // Has an existing attendee been removed?
            if (! in_array($contact->id, $attendeesID)) {
                $contact->activities()->detach($activity);
            }

            // Remove this ID from our list of contacts as we don't
            // want to add them to the activity again
            $idx = array_search($contact->id, $attendeesID);
            unset($attendeesID[$idx]);

            $contact->calculateActivitiesStatistics();
        }

        return $attendeesID;
    }

    /**
     * Create the association.
     *
     * @param array $data
     * @param Activity $activity
     * @param array $attendeesID
     * @return void
     */
    private function attach(array $data, Activity $activity, array $attendeesID)
    {
        foreach ($attendeesID as $contactId) {
            $contact = Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);

            $activity->contacts()->syncWithoutDetaching([$contactId => [
                'account_id' => $activity->account_id,
            ]]);

            $contact->calculateActivitiesStatistics();
        }
    }
}
