<?php

namespace App\Models\Contact;

use Carbon\Carbon;
use App\Traits\Hasher;
use App\Helpers\DBHelper;
use App\Models\User\User;
use App\Traits\Searchable;
use App\Models\Account\Event;
use App\Models\Journal\Entry;
use App\Mail\StayInTouchEmail;
use App\Models\Account\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Models\Relationship\Relationship;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Relationship\RelationshipType;
use App\Http\Resources\Tag\Tag as TagResource;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Address\AddressShort as AddressShortResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\ContactField\ContactField as ContactFieldResource;

class Contact extends Model
{
    use Searchable;
    use Hasher;

    protected $dates = [
        'last_talked_to',
        'last_consulted_at',
        'stay_in_touch_trigger_date',
        'created_at',
        'updated_at',
    ];

    // The list of columns we want the Searchable trait to use.
    protected $searchable_columns = [
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
    ];

    // The list of columns we want the Searchable trait to select.
    protected $return_from_search = [
        'id',
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
        'has_avatar',
        'avatar_file_name',
        'gravatar_url',
        'avatar_external_url',
        'default_avatar_color',
        'gender_id',
        'account_id',
        'created_at',
        'updated_at',
        'is_partial',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
        'gender_id',
        'account_id',
        'is_partial',
        'job',
        'company',
        'food_preferencies',
        'linkedin_profile_url',
        'is_dead',
        'avatar_external_url',
        'last_consulted_at',
        'created_at',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Eager load account with every contact.
     */
    protected $with = [
        'account',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_partial' => 'boolean',
        'is_dead' => 'boolean',
        'has_avatar' => 'boolean',
    ];

    /**
     * The name order attribute that indicates how to format the name of the
     * contact.
     *
     * @var string
     */
    protected $nameOrder = 'firstname_lastname';

    /**
     * Get the user associated with the contact.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the gender of the contact.
     *
     * @return HasOne
     */
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the activity records associated with the contact.
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->belongsToMany(Activity::class)->orderBy('date_it_happened', 'desc');
    }

    /**
     * Get the activity records associated with the contact.
     *
     * @return HasMany
     */
    public function activityStatistics()
    {
        return $this->hasMany(ActivityStatistic::class);
    }

    /**
     * Get the debt records associated with the contact.
     *
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * Get the gift records associated with the contact.
     *
     * @return HasMany
     */
    public function gifts()
    {
        return $this->hasMany(Gift::class);
    }

    /**
     * Get the event records associated with the contact.
     *
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the note records associated with the contact.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the reminder records associated with the contact.
     *
     * @return HasMany
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class)->orderBy('next_expected_date', 'asc');
    }

    /**
     * Get the task records associated with the contact.
     *
     * @return HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the tags records associated with the contact.
     *
     * @return HasMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withPivot('account_id')->withTimestamps();
    }

    /**
     * Get the calls records associated with the contact.
     *
     * @return HasMany
     */
    public function calls()
    {
        return $this->hasMany(Call::class)->orderBy('called_at', 'desc');
    }

    /**
     * Get the entries records associated with the contact.
     *
     * @return HasMany
     */
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get the Relationships records associated with the contact.
     *
     * @return HasMany
     */
    public function relationships()
    {
        return $this->hasMany(Relationship::class, 'contact_is');
    }

    /**
     * Get the Contact Field records associated with the contact.
     *
     * @return HasMany
     */
    public function contactFields()
    {
        return $this->hasMany(ContactField::class);
    }

    /**
     * Get the Address Field records associated with the contact.
     *
     * @return HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the Pets records associated with the contact.
     *
     * @return HasMany
     */
    public function pets()
    {
        return $this->hasMany(Pet::class);
    }

    /**
     * Get the contact records associated with the account.
     *
     * @return HasMany
     */
    public function specialDates()
    {
        return $this->hasMany(SpecialDate::class);
    }

    /**
     * Get the Special date represented the birthdate.
     *
     * @return HasOne
     */
    public function birthdate()
    {
        return $this->hasOne(SpecialDate::class, 'id', 'birthday_special_date_id');
    }

    /**
     * Get the Special date represented the deceased date.
     *
     * @return HasOne
     */
    public function deceasedDate()
    {
        return $this->hasOne(SpecialDate::class, 'id', 'deceased_special_date_id');
    }

    /**
     * Get the Special date represented the date first met.
     *
     * @return HasOne
     */
    public function firstMetDate()
    {
        return $this->hasOne(SpecialDate::class, 'id', 'first_met_special_date_id');
    }

    /**
     * Get the Notifications records associated with the account.
     *
     * @return HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Sort the contacts according a given criteria.
     * @param Builder $builder
     * @param string $criteria
     * @return Builder
     */
    public function scopeSortedBy(Builder $builder, $criteria)
    {
        switch ($criteria) {
            case 'firstnameAZ':
                return $builder->orderBy('first_name', 'asc');
            case 'firstnameZA':
                return $builder->orderBy('first_name', 'desc');
            case 'lastnameAZ':
                return $builder->orderBy('last_name', 'asc');
            case 'lastnameZA':
                return $builder->orderBy('last_name', 'desc');
            case 'lastactivitydateNewtoOld':
                $builder->leftJoin('activity_contact', 'contacts.id', '=', 'activity_contact.contact_id');
                $builder->leftJoin('activities', 'activity_contact.activity_id', '=', 'activities.id');
                $builder->orderBy('activities.date_it_happened', 'desc');
                $builder->select('*', 'contacts.id as id');

                return $builder;
            case 'lastactivitydateOldtoNew':
                $builder->leftJoin('activity_contact', 'contacts.id', '=', 'activity_contact.contact_id');
                $builder->leftJoin('activities', 'activity_contact.activity_id', '=', 'activities.id');
                $builder->orderBy('activities.date_it_happened', 'asc');
                $builder->select('*', 'contacts.id as id');

                return $builder;
            default:
                return $builder->orderBy('first_name', 'asc');
        }
    }

    /**
     * Scope a query to only include contacts who are not only a kid or a
     * significant other without being a contact.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReal($query)
    {
        return $query->where('is_partial', 0);
    }

    /**
     * Get the first name of the contact.
     *
     * @return string
     */
    public function getFirstNameAttribute($value)
    {
        return $value;
    }

    /**
     * Mutator first_name.
     *
     * @param string|null $value
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = trim($value);
    }

    /**
     * Get the last name of the contact.
     *
     * @return string
     */
    public function getLastNameAttribute($value)
    {
        return $value;
    }

    /**
     * Mutator last_name.
     *
     * It doesn't run ucfirst on purpose.
     *
     * @param string|null $value
     */
    public function setLastNameAttribute($value)
    {
        $value = $value ? trim($value) : null;
        $this->attributes['last_name'] = $value;
    }

    /**
     * Set the name order attribute.
     *
     * @param string $value
     * @return void
     */
    public function nameOrder($value)
    {
        $this->nameOrder = $value;
    }

    /**
     * Get the nickname of the contact.
     *
     * @return string
     */
    public function getNicknameAttribute($value)
    {
        return $value;
    }

    /**
     * Mutator last_name.
     *
     * @param string|null $value
     */
    public function setNicknameAttribute($value)
    {
        $value = $value ? trim($value) : null;
        $this->attributes['nickname'] = $value;
    }

    /**
     * Get user's initials.
     *
     * @return string
     */
    public function getInitialsAttribute()
    {
        preg_match_all('/(?<=\s|^)[a-zA-Z0-9]/i', $this->name, $initials);

        return implode('', $initials[0]);
    }

    /**
     * Mutator last_consulted_at.
     *
     * @param \DateTime $value
     */
    public function setLastConsultedAtAttribute($value)
    {
        $this->attributes['last_consulted_at'] = $value;
    }

    /**
     * Get the last consulted at date of the contact.
     *
     * @return string
     */
    public function getLastConsultedAtAttribute($value)
    {
        return $value;
    }

    /**
     * Get the full name of the contact.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        $completeName = '';

        if (Auth::check()) {
            $this->nameOrder = auth()->user()->name_order;
        }

        switch ($this->nameOrder) {
            case 'firstname_lastname':
                $completeName = $this->first_name;

                if (! is_null($this->middle_name)) {
                    $completeName = $completeName.' '.$this->middle_name;
                }

                if (! is_null($this->last_name)) {
                    $completeName = $completeName.' '.$this->last_name;
                }
                break;
            case 'lastname_firstname':
                $completeName = '';
                if (! is_null($this->last_name)) {
                    $completeName = $completeName.' '.$this->last_name;
                }

                if (! is_null($this->middle_name)) {
                    $completeName = $completeName.' '.$this->middle_name;
                }

                $completeName .= ' '.$this->first_name;
                break;
            case 'firstname_lastname_nickname':
                $completeName = $this->first_name;

                if (! is_null($this->middle_name)) {
                    $completeName = $completeName.' '.$this->middle_name;
                }

                if (! is_null($this->last_name)) {
                    $completeName = $completeName.' '.$this->last_name;
                }

                if (! is_null($this->nickname)) {
                    $completeName = $completeName.' ('.$this->nickname.')';
                }
                break;
            case 'firstname_nickname_lastname':
                $completeName = $this->first_name;

                if (! is_null($this->middle_name)) {
                    $completeName = $completeName.' '.$this->middle_name;
                }

                if (! is_null($this->nickname)) {
                    $completeName = $completeName.' ('.$this->nickname.')';
                }

                if (! is_null($this->last_name)) {
                    $completeName = $completeName.' '.$this->last_name;
                }

                break;
            case 'lastname_firstname_nickname':
                $completeName = '';
                if (! is_null($this->last_name)) {
                    $completeName = $this->last_name;
                }

                $completeName = $completeName.' '.$this->first_name;

                if (! is_null($this->middle_name)) {
                    $completeName = $completeName.' '.$this->middle_name;
                }

                if (! is_null($this->nickname)) {
                    $completeName = $completeName.' ('.$this->nickname.')';
                }
                break;
            case 'lastname_nickname_firstname':
                $completeName = '';
                if (! is_null($this->last_name)) {
                    $completeName = $this->last_name;
                }

                if (! is_null($this->nickname)) {
                    $completeName = $completeName.' ('.$this->nickname.')';
                }

                $completeName = $completeName.' '.$this->first_name;

                if (! is_null($this->middle_name)) {
                    $completeName = $completeName.' '.$this->middle_name;
                }
                break;
            case 'nickname':
                if (! is_null($this->nickname)) {
                    $completeName = $this->nickname;
                }

                if ($completeName == '') {
                    $completeName = $this->first_name;

                    if (! is_null($this->last_name)) {
                        $completeName = $completeName.' '.$this->last_name;
                    }
                }
                break;
        }

        if ($this->is_dead) {
            $completeName .= ' ⚰';
        }

        return trim($completeName);
    }

    /**
     * Get the incomplete name of the contact, like `John D.`.
     *
     * @return string
     */
    public function getIncompleteName()
    {
        $incompleteName = '';
        $incompleteName = $this->first_name;

        if (! is_null($this->last_name)) {
            $incompleteName = $incompleteName.' '.substr($this->last_name, 0, 1);
        }

        if ($this->is_dead) {
            $incompleteName .= ' ⚰';
        }

        return trim($incompleteName);
    }

    /**
     * Get the initials of the contact, used for avatars.
     *
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * Get the date of the last activity done by this contact.
     *
     * @return \DateTime
     */
    public function getLastActivityDate()
    {
        if ($this->activities->count() === 0) {
            return;
        }

        $lastActivity = $this->activities->sortByDesc('date_it_happened')->first();

        return $lastActivity->date_it_happened;
    }

    /**
     * Get the last talked to date.
     *
     * @return string
     */
    public function getLastCalled()
    {
        if (is_null($this->last_talked_to)) {
            return;
        }

        return $this->last_talked_to;
    }

    /**
     * Get the job of the contact.
     *
     * @return string
     */
    public function getJobAttribute($value)
    {
        return $value;
    }

    /**
     * Get the company the contact works at.
     *
     * @return string
     */
    public function getCompanyAttribute($value)
    {
        return $value;
    }

    /**
     * Get all the contacts related to the current contact by a specific
     * relationship type group.
     *
     * @param  string $type
     * @return Collection|null
     */
    public function getRelationshipsByRelationshipTypeGroup(String $type)
    {
        $relationshipTypeGroup = $this->account->getRelationshipTypeGroupByType($type);

        if (! $relationshipTypeGroup) {
            return;
        }

        return $this->relationships->filter(function ($item) use ($type) {
            return $item->relationshipType->relationshipTypeGroup->name == $type;
        });
    }

    /**
     * Translate a collection of relationships into a collection that the API can
     * parse.
     *
     * @param  Collection $collection
     * @param  bool       $shortVersion Indicates whether the collection should include how contacts are related
     * @return Collection
     */
    public static function translateForAPI(Collection $collection)
    {
        $contacts = collect();

        foreach ($collection as $relationship) {
            $contact = $relationship->ofContact;

            $contacts->push([
                'relationship' => [
                    'id' => $relationship->id,
                    'name' => $relationship->relationship_type_name,
                ],
                'contact' => new ContactShortResource($contact),
                ]);
        }

        return $contacts;
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
     * @param string|null $color
     * @return void
     */
    public function setAvatarColor($color = null)
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

        $this->default_avatar_color = $color ?? $colors[mt_rand(0, count($colors) - 1)];
    }

    /**
     * Log an event in the Event table about this contact.
     *
     * @param  string $objectType Contact, Activity, Kid,...
     * @param  int $objectId ID of the object
     * @param  string $natureOfOperation 'add', 'update', 'delete'
     * @return int                          Id of the created event
     */
    public function logEvent($objectType, $objectId, $natureOfOperation)
    {
        $event = $this->events()->make();
        $event->account_id = $this->account_id;
        $event->object_type = $objectType;
        $event->object_id = $objectId;
        $event->nature_of_operation = $natureOfOperation;
        $event->save();

        return $event->id;
    }

    /**
     * Set the name of the contact.
     *
     * @param  string $firstName
     * @param  string $middleName
     * @param  string $lastName
     * @return bool
     */
    public function setName(String $firstName, String $lastName = null, String $middleName = null)
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
     * Returns the state of the birthday.
     * As it's a Special Date, the date can have several states. We need this
     * info when we populate the Edit contact sheet.
     *
     * @return string
     */
    public function getBirthdayState()
    {
        if (! $this->birthday_special_date_id) {
            return 'unknown';
        }

        if ($this->birthdate->is_age_based) {
            return 'approximate';
        }

        // we know at least the day and month
        if ($this->birthdate->is_year_unknown) {
            return 'almost';
        }

        return 'exact';
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
     * Refresh statistics about activities.
     *
     * @return void
     */
    public function calculateActivitiesStatistics()
    {
        // Delete the Activities statistics table for this contact
        $this->activityStatistics->each->delete();

        // Create the statistics again
        $this->activities->groupBy('date_it_happened.year')
            ->map(function (Collection $activities, $year) {
                $activityStatistic = $this->activityStatistics()->make();
                $activityStatistic->account_id = $this->account_id;
                $activityStatistic->contact_id = $this->id;
                $activityStatistic->year = $year;
                $activityStatistic->count = $activities->count();
                $activityStatistic->save();
            });
    }

    /**
     * Get all the gifts offered, if any.
     */
    public function getGiftsOffered()
    {
        return $this->gifts()->offered()->get();
    }

    /**
     * Get all the gift ideas, if any.
     */
    public function getGiftIdeas()
    {
        return $this->gifts()->isIdea()->get();
    }

    /**
     * Get all the tasks in the in progress state, if any.
     */
    public function getTasksInProgress()
    {
        return $this->tasks()->inProgress()->get();
    }

    /**
     * Get all the tasks in the in completed state, if any.
     */
    public function getCompletedTasks()
    {
        return $this->tasks()->completed()->get();
    }

    /**
     * Returns the URL of the avatar with the given size.
     *
     * @param  int $size
     * @return string
     */
    public function getAvatarURL($size = 110)
    {
        // it either returns null or the gravatar url if it's defined
        if (! $this->has_avatar) {
            return $this->gravatar_url;
        }

        if ($this->avatar_location == 'external') {
            return $this->avatar_external_url;
        }

        $original_avatar_url = Storage::disk($this->avatar_location)->url($this->avatar_file_name);
        $avatar_filename = pathinfo($original_avatar_url, PATHINFO_FILENAME);
        $avatar_extension = pathinfo($original_avatar_url, PATHINFO_EXTENSION);
        $resized_avatar = 'avatars/'.$avatar_filename.'_'.$size.'.'.$avatar_extension;

        return asset(Storage::disk($this->avatar_location)->url($resized_avatar));
    }

    /**
     * Returns the source of the avatar, or null if avatar is undefined.
     */
    public function getAvatarSource()
    {
        if (! $this->has_avatar) {
            return;
        }

        if ($this->avatar_location == 'external') {
            return 'external';
        }

        return 'internal';
    }

    /**
     * Update the gravatar, using the firt email found.
     */
    public function updateGravatar()
    {
        // for performance reasons, we check if a gravatar exists for this email
        // address. if it does, we store the gravatar url in the database.
        // while this is not ideal because the gravatar can change, at least we
        // won't make constant call to gravatar to load the avatar on every
        // page load.
        $response = $this->getGravatar(250);
        if ($response !== false && is_string($response)) {
            $this->gravatar_url = $response;
        } else {
            $this->gravatar_url = null;
        }
        $this->save();
    }

    /**
     * Get the gravatar, if it exits.
     *
     * @param  int $size
     * @return string|bool
     */
    public function getGravatar($size)
    {
        $email = $this->getFirstEmail();

        if (is_null($email) || empty($email)) {
            return false;
        }

        if (! app('gravatar')->exists($email)) {
            return false;
        }

        return app('gravatar')->get($email, [
            'size' => $size,
            'secure' => config('app.env') === 'production',
        ]);
    }

    public function getFirstEmail()
    {
        $contact_email = $this->contactFields()
            ->whereHas('contactFieldType', function ($query) {
                $query->where('type', '=', 'email');
            })
            ->first();

        if (is_null($contact_email)) {
            return;
        }

        return $contact_email->data;
    }

    /**
     * Check if the contact has debt (by the contact or the user for this contact).
     *
     * @return bool
     */
    public function hasDebt()
    {
        return $this->debts()->count() !== 0;
    }

    /**
     * Get the list of tags as a string to populate the tags form.
     */
    public function getTagsAsString()
    {
        $tags = [];

        foreach ($this->tags as $tag) {
            array_push($tags, $tag->name);
        }

        return implode(',', $tags);
    }

    /**
     * Get the list of tags for this contact.
     */
    public function getTagsForAPI()
    {
        return TagResource::collection($this->tags);
    }

    /**
     * Get the list of addresses for this contact.
     */
    public function getAddressesForAPI()
    {
        return AddressShortResource::collection($this->addresses);
    }

    /**
     * Get the list of contact fields for this contact.
     */
    public function getContactFieldsForAPI()
    {
        return ContactFieldResource::collection($this->contactFields);
    }

    /**
     * Update the last called info on the contact, if the call has been made
     * in the most recent date.
     *
     * @param  Call   $call
     * @return void
     */
    public function updateLastCalledInfo(Call $call)
    {
        if (is_null($this->last_talked_to)) {
            $this->last_talked_to = $call->called_at;
        } else {
            $this->last_talked_to = $this->last_talked_to->max($call->called_at);
        }

        $this->save();
    }

    /**
     * Set a relationship between two contacts.
     *
     * @param Contact $otherContact
     * @param int $relationshipTypeId
     */
    public function setRelationship(self $otherContact, $relationshipTypeId)
    {
        $relationshipType = RelationshipType::find($relationshipTypeId);

        // Contact A is linked to Contact B
        $relationship = new Relationship;
        $relationship->account_id = $this->account_id;
        $relationship->relationship_type_id = $relationshipType->id;
        $relationship->contact_is = $this->id;
        $relationship->relationship_type_name = $relationshipType->name;
        $relationship->of_contact = $otherContact->id;
        $relationship->save();

        // Get the reverse relationship
        $reverseRelationshipType = $this->account->getRelationshipTypeByType($relationshipType->name_reverse_relationship);

        // Contact B is linked to Contact A
        $relationship = new Relationship;
        $relationship->account_id = $this->account_id;
        $relationship->relationship_type_id = $reverseRelationshipType->id;
        $relationship->contact_is = $otherContact->id;
        $relationship->relationship_type_name = $relationshipType->name_reverse_relationship;
        $relationship->of_contact = $this->id;
        $relationship->save();
    }

    /**
     * Update the relationship between two contacts.
     *
     * @param Contact $otherContact
     * @param int $relationshipTypeId
     */
    public function updateRelationship(self $otherContact, $oldRelationshipTypeId, $newRelationshipTypeId)
    {
        $this->deleteRelationship($otherContact, $oldRelationshipTypeId);

        $this->setRelationship($otherContact, $newRelationshipTypeId);
    }

    /**
     * Delete a relationship between two contacts.
     *
     * @param  self   $otherContact
     */
    public function deleteRelationship(self $otherContact, int $relationshipTypeId)
    {
        // Each relationship between two contacts has two Relationship objects.
        // We need to delete both.

        $relationship = Relationship::where('contact_is', $this->id)
                                    ->where('of_contact', $otherContact->id)
                                    ->where('relationship_type_id', $relationshipTypeId)
                                    ->first();

        $relationship->delete();

        $relationshipType = RelationshipType::find($relationshipTypeId);
        $reverseRelationshipType = $this->account->getRelationshipTypeByType($relationshipType->name_reverse_relationship);

        $relationship = Relationship::where('contact_is', $otherContact->id)
                                    ->where('of_contact', $this->id)
                                    ->where('relationship_type_id', $reverseRelationshipType->id)
                                    ->first();

        $relationship->delete();
    }

    /**
     * Is this contact owed money?
     * @return bool
     */
    public function isOwedMoney()
    {
        return $this->totalOutstandingDebtAmount() > 0;
    }

    /**
     * How much is the debt.
     * @return int
     */
    public function totalOutstandingDebtAmount()
    {
        return $this
            ->debts()
            ->where('status', '=', 'inprogress')
            ->getResults()
            ->sum(function ($d) {
                return $d->in_debt === 'yes' ? -$d->amount : $d->amount;
            });
    }

    /**
     * Indicates whether the contact has information about how they first met.
     * @return bool
     */
    public function hasFirstMetInformation()
    {
        return ! is_null($this->first_met_additional_info) || ! is_null($this->firstMetDate) || ! is_null($this->first_met_through_contact_id);
    }

    /**
     * Gets the contact who introduced this person to the user.
     * @return Contact
     */
    public function getIntroducer()
    {
        if (! $this->first_met_through_contact_id) {
            return;
        }

        try {
            $contact = self::where('account_id', $this->account_id)
                ->where('id', $this->first_met_through_contact_id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        return $contact;
    }

    /**
     * Sets a Special Date for this contact, for a specific occasion (birthday,
     * decease date,...) of which we know the date.
     *
     * @return SpecialDate
     */
    public function setSpecialDate($occasion, int $year, int $month, int $day)
    {
        if (null === $occasion) {
            return;
        }

        $specialDate = new SpecialDate;
        $specialDate->setToContact($this)->createFromDate($year, $month, $day);

        if ($occasion == 'birthdate') {
            $this->birthday_special_date_id = $specialDate->id;
        }

        if ($occasion == 'deceased_date') {
            $this->deceased_special_date_id = $specialDate->id;
        }

        if ($occasion == 'first_met') {
            $this->first_met_special_date_id = $specialDate->id;
        }

        $this->save();

        return $specialDate;
    }

    /**
     * Sets a Special Date for this contact, for a specific occasion (birthday,
     * decease date,...) of which we know only the age (meaning it's going to
     * be approximate).
     */
    public function setSpecialDateFromAge($occasion, int $age)
    {
        if (is_null($occasion)) {
            return;
        }

        $specialDate = new SpecialDate;
        $specialDate->setToContact($this)->createFromAge($age);

        if ($occasion == 'birthdate') {
            $this->birthday_special_date_id = $specialDate->id;
        }

        if ($occasion == 'deceased_date') {
            $this->deceased_special_date_id = $specialDate->id;
        }

        if ($occasion == 'first_met') {
            $this->first_met_special_date_id = $specialDate->id;
        }

        $this->save();

        return $specialDate;
    }

    /**
     * Removes the date that is set for a specific occasion (like a birthdate,
     * the deceased date,...).
     * @param string $occasion
     */
    public function removeSpecialDate($occasion)
    {
        if (null === $occasion) {
            return;
        }

        switch ($occasion) {
            case 'birthdate':
                if ($this->birthday_special_date_id) {
                    $birthdate = $this->birthdate;
                    $this->birthday_special_date_id = null;
                    $this->save();

                    $this->birthdate->deleteReminder();
                    $this->birthdate->delete();
                }
            break;
            case 'deceased_date':
                if ($this->deceased_special_date_id) {
                    $deceasedDate = $this->deceasedDate;
                    $this->deceased_special_date_id = null;
                    $this->save();

                    $deceasedDate->deleteReminder();
                    $deceasedDate->delete();
                }
            break;
            case 'first_met':
                if ($this->first_met_special_date_id) {
                    $firstMetDate = $this->firstMetDate;
                    $this->first_met_special_date_id = null;
                    $this->save();

                    $firstMetDate->deleteReminder();
                    $firstMetDate->delete();
                }
            break;
        }
    }

    /**
     * Sets a tag to the contact.
     *
     * @param string $tag
     * @return Tag
     */
    public function setTag(string $name)
    {
        $tag = $this->account->tags()->firstOrCreate([
            'name' => $name,
        ]);

        $tag->name_slug = str_slug($tag->name);
        $tag->save();

        $this->tags()->syncWithoutDetaching([$tag->id => ['account_id' => $this->account->id]]);

        return $tag;
    }

    /**
     * Unset all the tags associated with the contact.
     * @return bool
     */
    public function unsetTags()
    {
        $this->tags()->detach();
    }

    /**
     * Unset one tag associated with the contact.
     * @return bool
     */
    public function unsetTag(Tag $tag)
    {
        $this->tags()->detach($tag->id);
    }

    /**
     * Get the Relationship object representing the relation between two contacts.
     *
     * @param  Contact $otherContact
     * @return Relationship|null
     */
    public function getRelationshipNatureWith(self $otherContact)
    {
        return Relationship::where('contact_is', $this->id)
                                    ->where('of_contact', $otherContact->id)
                                    ->first();
    }

    /**
     * Delete the contact and all the related object.
     *
     * @return bool
     */
    public function deleteEverything()
    {
        // I know: this is a really brutal way of deleting objects. I'm doing
        // this because I'll add more objects related to contacts in the future
        // and I don't want to have to think of deleting a row that matches a
        // contact.
        //
        $tables = DBHelper::getTables();
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            try {
                DB::table($tableName)->where('contact_id', $this->id)->delete();
            } catch (QueryException $e) {
                continue;
            }
        }

        $this->delete();

        return true;
    }

    /**
     * Get all the reminders regarding the birthdays of the contacts who have a
     * relationships with the current contact.
     *
     * @return Collection
     */
    public function getBirthdayRemindersAboutRelatedContacts()
    {
        $reminders = collect();
        $relationships = $this->relationships->filter(function ($item) {
            return ! is_null($item->ofContact->birthday_special_date_id);
        });

        foreach ($relationships as $relationship) {
            $reminder = Reminder::find($relationship->ofContact->birthdate->reminder_id);

            if ($reminder) {
                $reminders->push($reminder);
            }
        }

        return $reminders;
    }

    /**
     * Gets the first contact related to this contact if the current contact is
     * partial.
     */
    public function getRelatedRealContact()
    {
        $relatedContact = Relationship::where('contact_is', $this->id)->first();

        if ($relatedContact) {
            return self::find($relatedContact->of_contact);
        }
    }

    /**
     * Get the contacts that have all the provided $tags
     * or if $tags is NONE get contacts that have no tags.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $tags string or Tag
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeTags($query, $tags)
    {
        if ($tags == 'NONE') {
            // get tagless contacts
            $query = $query->has('tags', '<', 1);
        } elseif (! empty($tags)) {
            // gets users who have all the tags
            foreach ($tags as $tag) {
                $query = $query->whereHas('tags', function ($query) use ($tag) {
                    $query->where('id', $tag->id);
                });
            }
        }

        return $query;
    }

    /**
     * Indicates the age of the contact at death.
     *
     * @return int
     */
    public function getAgeAtDeath()
    {
        return $this->deceasedDate->getAgeAtDeath();
    }

    /**
     * Update the frequency for which user has to be warned to stay in touch
     * with the contact.
     *
     * @param  int $frequency
     * @return bool
     */
    public function updateStayInTouchFrequency($frequency)
    {
        if (! is_int($frequency)) {
            return false;
        }

        $this->stay_in_touch_frequency = $frequency;

        if ($frequency == 0) {
            $this->stay_in_touch_frequency = null;
        }

        $this->save();

        return true;
    }

    /**
     * Update the date the notification about staying in touch should be sent.
     *
     * @param int $frequency
     * @param string $timezone
     */
    public function setStayInTouchTriggerDate($frequency, $timezone)
    {
        $now = Carbon::now($timezone);
        $newTriggerDate = $now->addDays($frequency);
        $this->stay_in_touch_trigger_date = $newTriggerDate;

        if ($frequency == 0) {
            $this->stay_in_touch_trigger_date = null;
        }

        $this->save();
    }

    /**
     * Send the email about staying in touch with the contact.
     *
     * @param  User $user
     * @return void
     */
    public function sendStayInTouchEmail(User $user)
    {
        Mail::to($user->email)->send(new StayInTouchEmail($this, $user));
    }
}
