<?php

namespace App;

use Auth;
use App\Note;
use App\Event;
use App\Debt;
use App\Account;
use App\Country;
use App\Reminder;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Gmopx\LaravelOWM\LaravelOWM;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $dates = [
        'birthdate',
    ];

    /**
     * Eager load user with every contact.
     */
    protected $with = [
        'user',
    ];

    /**
     * Get the user associated with the contact.
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'account_id');
    }

    /**
     * Get the complete name of the contact.
     *
     * @return string
     */
    public function getCompleteName()
    {
        $completeName = $this->first_name;

        if (! is_null($this->middle_name)) {
            $completeName = $completeName.' '.$this->middle_name;
        }

        if (! is_null($this->last_name)) {
            $completeName = $completeName.' '.$this->last_name;
        }

        return $completeName;
    }

    /**
     * Get the first name of the contact.
     *
     * @return string
     */
    public function getFirstName()
    {
        if (is_null($this->first_name)) {
            return null;
        }

        return $this->first_name;
    }

    /**
     * Get the middle name of the contact.
     *
     * @return string
     */
    public function getMiddleName()
    {
        if (is_null($this->middle_name)) {
            return null;
        }

        return $this->middle_name;
    }

    /**
     * Get the last name of the contact.
     *
     * @return string
     */
    public function getLastName()
    {
        if (is_null($this->last_name)) {
            return null;
        }

        return $this->last_name;
    }

    /**
     * Get the initials of the contact, used for avatars.
     *
     * @return string
     */
    public function getInitials()
    {
        $initials = $this->getFirstName()[0];

        if (! is_null($this->getMiddleName())) {
            $initial = $this->getMiddleName()[0];
            $initials .= $initial;
        }

        if (! is_null($this->getLastName())) {
            $initial = $this->getLastName()[0];
            $initials .= $initial;
        }

        return $initials;
    }

    /**
     * Get the date of the last activity done by this contact.
     *
     * @return string 'Oct 29, 1981'
     */
    public function getLastActivityDate($timezone)
    {
        $activity = Activity::where('account_id', $this->account_id)
                            ->where('contact_id', $this->id)
                            ->orderBy('date_it_happened', 'desc')
                            ->first();

        if (count($activity) == 0) {
            return null;
        }

        $last_activity_date = DateHelper::createDateFromFormat($activity->date_it_happened, $timezone);

        return DateHelper::getShortDate($last_activity_date, 'en');
    }

    /**
     * Get the last talked to date.
     *
     * @return string
     */
    public function getLastCalled($timezone)
    {
        if (is_null($this->last_talked_to)) {
            return null;
        }

        $lastTalkedTo = DateHelper::createDateFromFormat($this->last_talked_to, $timezone)->diffForHumans();

        return $lastTalkedTo;
    }

    /**
     * Get the birthdate of the contact.
     *
     * @return Carbon
     */
    public function getBirthdate()
    {
        if (is_null($this->birthdate)) {
            return null;
        }

        return $this->birthdate;
    }

    /**
     * Gets the age of the contact in years, or returns null if the birthdate
     * is not set.
     *
     * @return int
     */
    public function getAge()
    {
        if (is_null($this->birthdate)) {
            return null;
        }

        $age = $this->birthdate->diffInYears(Carbon::now());

        return $age;
    }

    /**
     * Get the phone number as a string.
     *
     * @return string or null
     */
    public function getPhone()
    {
        if (is_null($this->phone_number)) {
            return null;
        }

        return $this->phone_number;
    }

    /**
     * Returns 'true' if the birthdate is an approximation
     *
     * @return string
     */
    public function isBirthdateApproximate()
    {
        return $this->is_birthdate_approximate;
    }

    /**
     * Get the address in a format like 'Lives in Scranton, MS'.
     *
     * @return string
     */
    public function getPartialAddress()
    {
        $address = $this->getCity();

        if (is_null($address)) {
            return null;
        }

        if (! is_null($this->getProvince())) {
            $address = $address.', '.$this->getProvince();
        }

        return $address;
    }

    /**
     * Get the street of the contact.
     * @return string or null
     */
    public function getStreet()
    {
        if (is_null($this->street)) {
            return null;
        }

        return $this->street;
    }

    /**
     * Get the province of the contact.
     * @return string or null
     */
    public function getProvince()
    {
        if (is_null($this->province)) {
            return null;
        }

        return $this->province;
    }

    /**
     * Get the postal code of the contact.
     * @return string or null
     */
    public function getPostalCode()
    {
        if (is_null($this->postal_code)) {
            return null;
        }

        return $this->postal_code;
    }

    /**
     * Get the country of the contact.
     * @return string or null
     */
    public function getCountryName()
    {
        $country = null;

        if (! is_null($this->country_id)) {
            $country = Country::findOrFail($this->country_id);
            $country = $country->country;
        }

        return $country;
    }

    /**
     * Get the city.
     * @return string
     */
    public function getCity()
    {
        if (is_null($this->city)) {
            return null;
        }

        return $this->city;
    }

    /**
     * Get the countryID of the contact.
     * @return string or null
     */
    public function getCountryID()
    {
        return $this->country_id;
    }

    /**
     * Get the country ISO of the contact.
     * @return string or null
     */
    public function getCountryISO()
    {
        $country = Country::find($this->country_id);

        if (count($country) == 0) {
            return null;
        }

        return $country->iso;
    }

    /**
     * Get an URL for Google Maps for the address.
     *
     * @return string
     */
    public function getGoogleMapAddress()
    {
        $address = $this->getFullAddress();
        $address = urlencode($address);

        return "https://www.google.ca/maps/place/{$address}";
    }

    /**
     * Get the last updated date.
     *
     * @return string Y-m-d
     */
    public function getLastUpdated()
    {
        return DateHelper::createDateFromFormat($this->updated_at, $this->user->timezone)->format('Y/m/d');
    }

    /**
     * Get the total number of reminders.
     *
     * @return int
     */
    public function getNumberOfReminders()
    {
        return $this->number_of_reminders;
    }

    /**
     * Get the total number of kids.
     *
     * @return int
     */
    public function getNumberOfKids()
    {
        return $this->number_of_kids;
    }

    /**
     * Get the total number of activities.
     *
     * @return int
     */
    public function getNumberOfActivities()
    {
        return $this->number_of_activities;
    }

    /**
     * Get the total number of gifts, regardless of ideas or offered.
     *
     * @return int
     */
    public function getNumberOfGifts()
    {
        return $this->number_of_gifts_ideas + $this->number_of_gifts_offered;
    }

    /**
     * Gets the email address or returns null if undefined.
     *
     * @return string
     */
    public function getEmail()
    {
        if (is_null($this->email)) {
            return null;
        }

        return $this->email;
    }

    /**
     * Gets the Twitter URL or returns null if undefined.
     *
     * @return string
     */
    public function getTwitter()
    {
        if (is_null($this->twitter_profile_url)) {
            return null;
        }

        return $this->twitter_profile_url;
    }

    /**
     * Gets the Facebook URL or returns null if undefined.
     *
     * @return string
     */
    public function getFacebook()
    {
        if (is_null($this->facebook_profile_url)) {
            return null;
        }

        return $this->facebook_profile_url;
    }

    /**
     * Gets the LinkedIn URL or returns null if undefined.
     *
     * @return string
     */
    public function getLinkedin()
    {
        if (is_null($this->linkedin_profile_url)) {
            return null;
        }

        return $this->linkedin_profile_url;
    }

    /**
     * Get the current Significant Other, if it exists, or return null otherwise.
     *
     * @return SignificantOther
     */
    public function getCurrentSignificantOther()
    {
        $significantOther = SignificantOther::where('account_id', $this->account_id)
                                ->where('contact_id', $this->id)
                                ->where('status', 'active')
                                ->first();

        return $significantOther;
    }

    /**
     * Get the notes for this contact. Return an empty collection if no notes.
     *
     * @return Notes
     */
    public function getNotes()
    {
        $notes = Note::where('contact_id', $this->id)
                          ->where('account_id', $this->account_id)
                          ->orderBy('id', 'desc')
                          ->get();

        return $notes;
    }

    /**
     * Get the number of notes for this contact.
     *
     * @return int
     */
    public function getNumberOfNotes()
    {
        return $this->number_of_notes;
    }

    /**
     * Get the kids, if any, as a collection.
     *
     * @return Collection
     */
    public function getKids()
    {
        $kids = Kid::where('account_id', $this->account_id)
                        ->where('child_of_contact_id', $this->id)
                        ->get();

        return $kids;
    }

    /**
     * Gets the food preferencies or return null if not defined.
     *
     * @return string
     */
    public function getFoodPreferencies()
    {
        if (is_null($this->food_preferencies)) {
            return null;
        }

        return $this->food_preferencies;
    }

    /**
     * Get the default color of the avatar if no picture is present.
     *
     * @return string
     */
    public function getAvatarColor()
    {
        return $this->default_avatar_color;
    }

    /**
     * Set the default avatar color for this object.
     *
     * @return void
     */
    public function setAvatarColor()
    {
        $colors = [
          '#fdb660',
          '#93521e',
          '#bd5067',
          '#b3d5fe',
          '#ff9807',
          '#709512',
          '#5f479a',
          '#e5e5cd',
        ];
        $randomColorChosen = $colors[mt_rand(0, count($colors) - 1)];

        $this->default_avatar_color = $randomColorChosen;
        $this->save();
    }

    /**
     * Log an event in the Event table about this contact.
     *
     * @param  string $objectType           Contact, Activity, Kid,...
     * @param  int $objectId                ID of the object
     * @param  string $natureOfOperation    'add', 'edit', 'delete'
     * @return int                          Id of the created event
     */
    public function logEvent($objectType, $objectId, $natureOfOperation)
    {
        $event = new Event;
        $event->account_id = $this->account_id;
        $event->contact_id = $this->id;
        $event->object_type = $objectType;
        $event->object_id = $objectId;
        $event->nature_of_operation = $natureOfOperation;
        $event->save();

        return $event->id;
    }

    /**
     * Add a significant other.
     *
     * @param string $name
     * @param string $gender
     * @param bool $birthdate_approximate
     * @param string $birthdate
     * @param int $age
     * @return int
     */
    public function addSignificantOther($firstname, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        $significantOther = new SignificantOther;
        $significantOther->account_id = $this->account_id;
        $significantOther->contact_id = $this->id;
        $significantOther->first_name = ucfirst($firstname);
        $significantOther->gender = $gender;
        $significantOther->is_birthdate_approximate = $birthdate_approximate;
        $significantOther->status = 'active';
        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $significantOther->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $significantOther->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $significantOther->birthdate = $birthdate;
        }
        $significantOther->save();

        // Event
        $this->logEvent('significantother', $significantOther->id, 'create');

        return $significantOther->id;
    }

    /**
     * Update the information about the Significant other.
     *
     * @param  int $significantOtherId
     * @param  string $firstname
     * @param  string $lastname
     * @param  string $gender
     * @param  string $birthdate_approximate
     * @param  string $birthdate
     * @param  int $age
     * @param  string $timezone
     * @return Response
     */
    public function editSignificantOther($significantOtherId, $firstname, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        $significantOther = SignificantOther::findOrFail($significantOtherId);

        $significantOther->first_name = ucfirst($firstname);
        $significantOther->gender = $gender;
        $significantOther->is_birthdate_approximate = $birthdate_approximate;
        $significantOther->status = 'active';

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $significantOther->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $significantOther->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $significantOther->birthdate = $birthdate;
        }

        $significantOther->save();

        // Event
        $this->logEvent('significantother', $significantOther->id, 'update');

        return $significantOther->id;
    }

    /**
     * Delete the significant other.
     */
    public function deleteSignificantOther($significantOtherId)
    {
        $significantOther = SignificantOther::findOrFail($significantOtherId);
        $significantOther->delete();

        $events = Event::where('contact_id', $significantOther->contact_id)
                          ->where('account_id', $significantOther->account_id)
                          ->where('object_type', 'significantother')
                          ->where('object_id', $significantOther->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }

    /**
     * Update the name of the contact.
     *
     * @param  string $firstName
     * @param  string $middleName
     * @param  string $lastName
     * @return bool
     */
    public function updateName($firstName, $middleName, $lastName)
    {
        if ($firstName == '') {
            return false;
        }

        $this->first_name = $firstName;

        if (! is_null($middleName)) {
            $this->middle_name = $middleName;
        }

        if (! is_null($lastName)) {
            $this->last_name = $lastName;
        }
        $this->save();

        return true;
    }

    /**
     * Update the name of the contact.
     *
     * @param  string $foodPreferencies
     * @return void
     */
    public function updateFoodPreferencies($foodPreferencies)
    {
        if ($foodPreferencies == '') {
            $this->food_preferencies = null;
        } else {
            $this->food_preferencies = $foodPreferencies;
        }

        $this->save();
    }

    /**
     * Add a kid.
     *
     * @param string $name
     * @param string $gender
     * @param bool $birthdate_approximate
     * @param string $birthdate
     * @param int $age
     * @return int the Kid ID
     */
    public function addKid($name, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        $kid = new Kid;
        $kid->account_id = $this->account_id;
        $kid->child_of_contact_id = $this->id;
        $kid->first_name = ucfirst($name);
        $kid->gender = $gender;
        $kid->is_birthdate_approximate = $birthdate_approximate;

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $kid->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $kid->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $kid->birthdate = $birthdate;
        }

        $kid->save();

        $this->has_kids = 'true';
        $this->number_of_kids = $this->number_of_kids + 1;
        $this->save();

        $this->logEvent('kid', $kid->id, 'create');

        return $kid->id;
    }

    /**
     * Edit a kid.
     *
     * @param string $name
     * @param string $gender
     * @param bool $birthdate_approximate
     * @param string $birthdate
     * @param int $age
     * @return int the Kid ID
     */
    public function editKid($kidId, $name, $gender, $birthdate_approximate, $birthdate, $age, $timezone)
    {
        $kid = Kid::findOrFail($kidId);
        $kid->first_name = ucfirst($name);
        $kid->gender = $gender;
        $kid->is_birthdate_approximate = $birthdate_approximate;

        if ($birthdate_approximate == 'approximate') {
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $kid->birthdate = $birthdate;
        } elseif ($birthdate_approximate == 'unknown') {
            $kid->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $birthdate);
            $kid->birthdate = $birthdate;
        }

        $kid->save();

        $this->logEvent('kid', $kid->id, 'update');

        return $kid->id;
    }

    /**
     * Delete the kid.
     */
    public function deleteKid($kidId)
    {
        $kid = Kid::findOrFail($kidId);
        $kid->delete();

        // Delete all events
        $events = Event::where('contact_id', $kid->child_of_contact_id)
                          ->where('account_id', $kid->account_id)
                          ->where('object_type', 'kid')
                          ->where('object_id', $kid->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }

        // Decrease number of kids
        $this->number_of_kids = $this->number_of_kids - 1;

        if ($this->number_of_kids < 1) {
            $this->number_of_kids = 0;
            $this->has_kids = 'false';
        }
        $this->save();
    }

    /**
     * Create a note.
     *
     * @param string
     */
    public function addNote($body)
    {
        $note = new Note;
        $note->account_id = $this->account_id;
        $note->contact_id = $this->id;
        $note->body = $body;
        $note->save();

        $this->number_of_notes = $this->number_of_notes + 1;
        $this->save();

        $this->logEvent('note', $note->id, 'create');

        return $note->id;
    }

    /**
     * Delete the note.
     */
    public function deleteNote($noteId)
    {
        $note = Note::findOrFail($noteId);
        $note->delete();

        // Decrease number of notes
        $this->number_of_notes = $this->number_of_notes - 1;

        if ($this->number_of_notes < 1) {
            $this->number_of_notes = 0;
        }
        $this->save();

        // Delete all events
        $events = Event::where('contact_id', $note->contact_id)
                          ->where('account_id', $note->account_id)
                          ->where('object_type', 'note')
                          ->where('object_id', $note->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }

    /**
     * Get all the activities, if any.
     *
     * @return Collection
     */
    public function getActivities()
    {
        $activities = Activity::where('account_id', $this->account_id)
                                ->where('contact_id', $this->id)
                                ->orderBy('date_it_happened', 'desc')
                                ->get();

        return $activities;
    }

    /**
     * Refresh statistics about activities
     * TODO: unit test.
     *
     * @return void
     */
    public function calculateActivitiesStatistics()
    {
        // Delete the Activities statistics table for this contact
        $activitiesStatistics = ActivityStatistic::where('account_id', $this->account_id)
                                                    ->where('contact_id', $this->id)
                                                    ->get();

        foreach ($activitiesStatistics as $activityStatistic) {
            $activityStatistic->delete();
        }

        // Create the statistics again
        foreach ($this->getActivities() as $activity) {
            $year = $activity->date_it_happened->year;

            // Check to see if there is a year for this activity already
            $activityStatistic = ActivityStatistic::where('account_id', $this->account_id)
                                                ->where('contact_id', $this->id)
                                                ->where('year', $year)
                                                ->first();

            if (count($activityStatistic) == 0) {

                // Year does not exist, create the record
                $activityStatistic = new ActivityStatistic;
                $activityStatistic->account_id = $this->account_id;
                $activityStatistic->contact_id = $this->id;
                $activityStatistic->year = $year;
                $activityStatistic->count = 1;
                $activityStatistic->save();

            } else {

                // Year does exist, increment the record
                $activityStatistic->count = $activityStatistic->count + 1;
                $activityStatistic->save();
            }
        }
    }

    /**
     * Get statistics for the contact
     * TODO: add unit test.
     *
     * @param  int $contactId
     */
    public function getActivitiesStats()
    {
        $statistics = ActivityStatistic::where('contact_id', $this->id)
                                        ->orderBy('year', 'desc')
                                        ->get();

        return $statistics;
    }

    /**
     * Get all the reminders, if any.
     *
     * @return Collection
     */
    public function getReminders()
    {
        $reminders = Reminder::where('account_id', $this->account_id)
                                ->where('contact_id', $this->id)
                                ->orderBy('next_expected_date', 'asc')
                                ->get();

        return $reminders;
    }

    /**
     * Get all the gifts, if any.
     *
     * @return Collection
     */
    public function getGifts()
    {
        return Gift::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->get();
    }

    /**
     * Get all the gifts offered, if any.
     */
    public function getGiftsOffered()
    {
        return Gift::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->where('has_been_offered', 'true')
                        ->get();
    }

    /**
     * Get all the gift ideas, if any.
     */
    public function getGiftIdeas()
    {
        return Gift::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->where('is_an_idea', 'true')
                        ->get();
    }

    /**
     * Get all the tasks in the in progress state, if any.
     */
    public function getTasksInProgress()
    {
        return Task::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->where('status', 'inprogress')
                        ->get();
    }

    /**
     * Get all the tasks no matter the state, if any.
     */
    public function getTasks()
    {
        return Task::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->get();
    }

    /**
     * Get all the tasks in the in completed state, if any.
     */
    public function getCompletedTasks()
    {
        return Task::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->where('status', 'completed')
                        ->get();
    }

    /**
     * Returns the URL of the avatar with the given size
     * @param  int $size
     * @return string
     */
    public function getAvatarURL($size)
    {
        $original_avatar_url = Storage::disk('public')->url($this->avatar_file_name);
        $avatar_filename = pathinfo($original_avatar_url, PATHINFO_FILENAME);
        $avatar_extension = pathinfo($original_avatar_url, PATHINFO_EXTENSION);
        $resized_avatar = 'avatars/'.$avatar_filename.'_'.$size.'.'.$avatar_extension;

        return Storage::disk('public')->url($resized_avatar);
    }

    public function getGravatar($size)
    {
        if ( empty( $this->email ) ) {
            return false;
        }
        $gravatar_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $this->email ) ) );
        // check if gravatar exists by appending ?d=404, returns 404 response if does not exist
        $gravatarHeaders = get_headers( $gravatar_url . "?d=404" );
        if ($gravatarHeaders[0] == "HTTP/1.1 404 Not Found") {
            return false;
        }

        return $gravatar_url . "?s=" . $size;
    }

    /**
     * Check if the contact has debt (by the contact or the user for this contact)
     * @return int amount
     */
    public function hasDebt()
    {
        return Debt::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->where('status', 'inprogress')
                        ->count();
    }

    /**
     * Get all the tasks no matter the state, if any.
     */
    public function getDebts()
    {
        return Debt::where('account_id', $this->account_id)
                        ->where('contact_id', $this->id)
                        ->get();
    }

}
