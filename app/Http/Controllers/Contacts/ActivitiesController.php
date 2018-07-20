<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;

class ActivitiesController extends Controller
{
    /**
     * Get all the activities for this contact.
     */
    public function index(Contact $contact)
    {
        $contactActivities = collect([]);

        foreach ($contact->activities as $activity) {
            $data = [
                'id' => $activity->id,
                'activity_type_id' => $activity->getGoogleMapAddress(),
                'activity_type_name' => $activity->getFullAddress(),
                'summary' => $activity->country,
                'description' => $activity->country_name,
                'date_it_happened' => $activity->street,
            ];
            $contactActivities->push($data);
        }

        return $contactActivities;
    }
}
