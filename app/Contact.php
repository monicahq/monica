<?php

namespace App;

use App\Traits\Searchable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\Tag\Tag as TagResource;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Http\Resources\Address\AddressShort as AddressShortResource;
use App\Http\Resources\Contact\PartnerShort as PartnerShortResource;
use App\Http\Resources\Contact\OffspringShort as OffspringShortResource;
use App\Http\Resources\Contact\ProgenitorShort as ProgenitorShortResource;

class Contact extends Model
{
    use Searchable;

    protected $dates = [
        'last_talked_to',
        'last_consulted_at',
        'created_at',
        'updated_at',
    ];

    // The list of columns we want the Searchable trait to use.
    protected $searchable_columns = [
        'first_name',
        'middle_name',
        'last_name',
        'food_preferencies',
        'job',
        'company',
    ];

    // The list of columns we want the Searchable trait to select.
    protected $return_from_search = [
        'id',
        'first_name',
        'middle_name',
        'last_name',
        'has_avatar',
        'avatar_file_name',
        'gravatar_url',
        'avatar_external_url',
        'default_avatar_color',
        'gender_id',
        'account_id',
        'created_at',
        'updated_at',
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
     * Get the user associated with the contact.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the gender of the contact.
     *
     * @return HasOne
     */
    public function gender()
    {
        return $this->belongsTo('App\Gender');
    }

    /**
     * Get the activity records associated with the contact.
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->belongsToMany('App\Activity')->orderBy('date_it_happened', 'desc');
    }

    /**
     * Get the activity records associated with the contact.
     *
     * @return HasMany
     */
    public function activityStatistics()
    {
        return $this->hasMany('App\ActivityStatistic');
    }

    /**
     * Get the debt records associated with the contact.
     *
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany('App\Debt');
    }

    /**
     * Get the gift records associated with the contact.
     *
     * @return HasMany
     */
    public function gifts()
    {
        return $this->hasMany('App\Gift');
    }

    /**
     * Get the event records associated with the contact.
     *
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany('App\Event')->orderBy('created_at', 'desc');
    }

    /**
     * Get the note records associated with the contact.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Get the reminder records associated with the contact.
     *
     * @return HasMany
     */
    public function reminders()
    {
        return $this->hasMany('App\Reminder')->orderBy('next_expected_date', 'asc');
    }

    /**
     * Get the task records associated with the contact.
     *
     * @return HasMany
     */
    public function tasks()
    {
        return $this->hasMany('App\Task');
    }

    /**
     * Get the tags records associated with the contact.
     *
     * @return HasMany
     */
    public function tags()
    {
        return $this->belongsToMany('App\Tag')->withPivot('account_id')->withTimestamps();
    }

    /**
     * Get the calls records associated with the contact.
     *
     * @return HasMany
     */
    public function calls()
    {
        return $this->hasMany('App\Call')->orderBy('called_at', 'desc');
    }

    /**
     * Get the entries records associated with the contact.
     *
     * @return HasMany
     */
    public function entries()
    {
        return $this->hasMany('App\Entry');
    }

    /**
     * Get the Relationships records associated with the contact.
     *
     * @return HasMany
     */
    public function activeRelationships()
    {
        return $this->hasMany('App\Relationship', 'contact_id')->where('is_active', 1);
    }

    /**
     * Get the Offsprings records associated with the contact.
     *
     * @return HasMany
     */
    public function offsprings()
    {
        return $this->hasMany('App\Offspring', 'is_the_child_of');
    }

    /**
     * Get the Progenitors records associated with the contact.
     *
     * @return HasMany
     */
    public function progenitors()
    {
        return $this->hasMany('App\Progenitor', 'is_the_parent_of');
    }

    /**
     * Get the Contact Field records associated with the contact.
     *
     * @return HasMany
     */
    public function contactFields()
    {
        return $this->hasMany('App\ContactField');
    }

    /**
     * Get the Address Field records associated with the contact.
     *
     * @return HasMany
     */
    public function addresses()
    {
        return $this->hasMany('App\Address');
    }

    /**
     * Get the Pets records associated with the contact.
     *
     * @return HasMany
     */
    public function pets()
    {
        return $this->hasMany('App\Pet');
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
        return $this->hasOne('App\SpecialDate', 'id', 'birthday_special_date_id');
    }

    /**
     * Get the Special date represented the deceased date.
     *
     * @return HasOne
     */
    public function deceasedDate()
    {
        return $this->hasOne('App\SpecialDate', 'id', 'deceased_special_date_id');
    }

    /**
     * Get the Special date represented the date first met.
     *
     * @return HasOne
     */
    public function firstMetDate()
    {
        return $this->hasOne('App\SpecialDate', 'id', 'first_met_special_date_id');
    }

    /**
     * Get the Notifications records associated with the account.
     *
     * @return HasMany
     */
    public function notifications()
    {
        return $this->hasMany('App\Notification');
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
     * Get user's initials.
     *
     * @return string
     */
    public function getInitialsAttribute()
    {
        preg_match_all('/(?<=\s|^)[a-zA-Z0-9]/i', $this->getCompleteName(), $initials);

        return implode('', $initials[0]);
    }

    /**
     * Mutator last_consulted_at.
     *
     * @param datetime $value
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
     * Get the complete name of the contact.
     *
     * @return string
     */
    public function getCompleteName($nameOrder = 'firstname_first')
    {
        $completeName = '';

        if ($nameOrder == 'firstname_first') {
            $completeName = $this->first_name;

            if (! is_null($this->middle_name)) {
                $completeName = $completeName.' '.$this->middle_name;
            }

            if (! is_null($this->last_name)) {
                $completeName = $completeName.' '.$this->last_name;
            }
        } else {
            if (! is_null($this->last_name)) {
                $completeName = $this->last_name;
            }

            if (! is_null($this->middle_name)) {
                $completeName = $completeName.' '.$this->middle_name;
            }

            $completeName = $completeName.' '.$this->first_name;
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
     * @return DateTime
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
     * Get the current Significant Others, if they exists, or return null otherwise.
     *
     * @return Collection
     */
    public function getCurrentPartners()
    {
        $partners = collect([]);
        foreach ($this->activeRelationships as $relationship) {
            $contact = self::find($relationship->with_contact_id);
            $partners->push($contact);
        }

        return $partners;
    }

    /**
     * Get the current Significant Others as ID, if they exists, or return null otherwise.
     *
     * @return Collection
     */
    public function getCurrentPartnersForAPI()
    {
        $partners = collect([]);
        foreach ($this->activeRelationships as $relationship) {
            $contact = self::find($relationship->with_contact_id);
            $partners->push(new PartnerShortResource($contact));
        }

        return $partners;
    }

    /**
     * Get the Kids, if they exists, or return null otherwise.
     *
     * @return Collection
     */
    public function getOffsprings()
    {
        $kids = collect([]);
        foreach ($this->offsprings as $offspring) {
            $contact = self::find($offspring->contact_id);
            $kids->push($contact);
        }

        return $kids;
    }

    /**
     * Get the Kids, if they exists, or return null otherwise.
     *
     * @return Collection
     */
    public function getOffspringsForAPI()
    {
        $kids = collect([]);
        foreach ($this->offsprings as $offspring) {
            $contact = self::find($offspring->contact_id);
            $kids->push(new OffspringShortResource($contact));
        }

        return $kids;
    }

    /**
     * Get the current parents, if they exists, or return null otherwise.
     *
     * @return Collection
     */
    public function getProgenitors()
    {
        $progenitors = collect([]);
        foreach ($this->progenitors as $progenitor) {
            $contact = self::find($progenitor->contact_id);
            $progenitors->push($contact);
        }

        return $progenitors;
    }

    /**
     * Get the current parents, if they exists, or return null otherwise.
     *
     * @return Collection
     */
    public function getProgenitorsForAPI()
    {
        $progenitors = collect([]);
        foreach ($this->progenitors as $progenitor) {
            $contact = self::find($progenitor->contact_id);
            $progenitors->push(new ProgenitorShortResource($contact));
        }

        return $progenitors;
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

        $this->save();
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
        $event = $this->events()->create([]);
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
    public function setName(String $firstName, String $middleName = null, String $lastName)
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
     * Refresh statistics about activities
     * TODO: unit test.
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
                $activityStatistic = $this->activityStatistics()->create([]);
                $activityStatistic->account_id = $this->account_id;
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
     * Get the gravatar, if it exits.
     *
     * @param  int $size
     * @return string|bool
     */
    public function getGravatar($size)
    {
        if (empty($this->email)) {
            return false;
        }
        $gravatar_url = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email)));
        // check if gravatar exists by appending ?d=404, returns 404 response if does not exist
        $gravatarHeaders = get_headers($gravatar_url.'?d=404');
        if ($gravatarHeaders[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }

        return $gravatar_url.'?s='.$size;
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
     * Get the list of all potential contacts to add as either a significant
     * other or a kid.
     *
     * @return Collection
     */
    public function getPotentialContacts()
    {
        $partners = self::where('account_id', $this->account_id)
                            ->where('is_partial', 0)
                            ->where('id', '!=', $this->id)
                            ->orderBy('first_name', 'asc')
                            ->orderBy('last_name', 'asc')
                            ->get();

        // Filter out the contacts who already partner with the given contact
        $counter = 0;
        foreach ($partners as $partner) {
            $relationship = Relationship::where('contact_id', $this->id)
                                    ->where('with_contact_id', $partner->id)
                                    ->count();

            $offspring = Offspring::where('contact_id', $partner->id)
                                    ->where('is_the_child_of', $this->id)
                                    ->count();

            $progenitor = Progenitor::where('contact_id', $partner->id)
                                    ->where('is_the_parent_of', $this->id)
                                    ->count();

            if ($relationship != 0 || $offspring != 0 || $progenitor != 0) {
                $partners->forget($counter);
            }
            $counter++;
        }

        return $partners;
    }

    /**
     * Get the list of partners who are not "real" contacts.
     *
     * @return Collection
     */
    public function getPartialPartners()
    {
        $relationships = Relationship::where('contact_id', $this->id)
                                    ->get();

        $partners = collect();
        foreach ($relationships as $relationship) {
            $partner = self::findOrFail($relationship->with_contact_id);

            if ($partner->is_partial) {
                $partners->push($partner);
            }
        }

        return $partners;
    }

    /**
     * Get the list of kids who are not "real" contacts.
     *
     * @return Collection
     */
    public function getPartialOffsprings()
    {
        $offsprings = Offspring::where('is_the_child_of', $this->id)
                                    ->get();

        $kids = collect();
        foreach ($offsprings as $offspring) {
            $kid = self::findOrFail($offspring->contact_id);

            if ($kid->is_partial) {
                $kids->push($kid);
            }
        }

        return $kids;
    }

    /**
     * Set a relationship between the two contacts. Has the option to set a
     * bilateral relationship if the partner is a real contact.
     *
     * @param Contact $partner
     * @param  bool $bilateral
     */
    public function setRelationshipWith(self $partner, $bilateral = false)
    {
        Relationship::create(
            [
                'account_id' => $this->account_id,
                'contact_id' => $this->id,
                'with_contact_id' => $partner->id,
                'is_active' => 1,
            ]
        );

        if ($bilateral) {
            Relationship::create(
                [
                    'account_id' => $this->account_id,
                    'contact_id' => $partner->id,
                    'with_contact_id' => $this->id,
                    'is_active' => 1,
                ]
            );
        }
    }

    /**
     * Set a unilateral relationship to a bilateral one between the two contacts.
     *
     * @param Contact $partner
     * @param  bool $bilateral
     */
    public function updateRelationshipWith(self $partner)
    {
        Relationship::create(
            [
                'account_id' => $this->account_id,
                'contact_id' => $partner->id,
                'with_contact_id' => $this->id,
                'is_active' => 1,
            ]
        );
    }

    /**
     * Set a relationship between the two contacts. Has the option to set a
     * bilateral relationship if the kid is a real contact.
     *
     * @param Contact $parent
     * @param  bool $bilateral
     */
    public function isTheOffspringOf(self $parent, $bilateral = false)
    {
        Offspring::create(
            [
                'account_id' => $this->account_id,
                'contact_id' => $this->id,
                'is_the_child_of' => $parent->id,
            ]
        );

        if ($bilateral) {
            Progenitor::create(
                [
                    'account_id' => $this->account_id,
                    'contact_id' => $parent->id,
                    'is_the_parent_of' => $this->id,
                ]
            );
        }
    }

    /**
     * Unset a relationship between the two contacts.
     *
     * @param  Contact $partner
     * @param  bool $bilateral
     */
    public function unsetRelationshipWith(self $partner, $bilateral = false)
    {
        $relationship = Relationship::where('contact_id', $this->id)
                        ->where('with_contact_id', $partner->id)
                        ->first();

        $relationship->delete();

        if ($bilateral) {
            $relationship = Relationship::where('contact_id', $partner->id)
                        ->where('with_contact_id', $this->id)
                        ->first();

            $relationship->delete();
        }
    }

    /**
     * Unset a parenting relationship between the two contacts.
     *
     * @param  Contact $kid
     * @param  bool $bilateral
     */
    public function unsetOffspring(self $kid, $bilateral = false)
    {
        $offspring = Offspring::where('contact_id', $kid->id)
                        ->where('is_the_child_of', $this->id)
                        ->first();

        $offspring->delete();

        if ($bilateral) {
            $progenitor = Progenitor::where('contact_id', $this->id)
                        ->where('is_the_parent_of', $kid->id)
                        ->first();

            $progenitor->delete();
        }
    }

    /**
     * Deletes all the events that mentioned the relationship with this partner.
     *
     * @var Contact
     */
    public function deleteEventsAboutTheseTwoContacts(self $contact, $type)
    {
        Event::where('contact_id', $this->id)
                ->where('object_id', $contact->id)
                ->where('object_type', $type)
                ->delete();

        Event::where('contact_id', $contact->id)
                ->where('object_id', $this->id)
                ->where('object_type', $type)
                ->delete();
    }

    /**
     * Get all the reminders about the contact, and also about the relatives
     * (significant others and kids).
     *
     * @return Collection
     */
    public function getRemindersAboutRelatives()
    {
        $reminders = $this->reminders;

        $partners = $this->getPartialPartners();
        foreach ($partners as $partner) {
            foreach ($partner->reminders as $reminder) {
                $reminders->push($reminder);
            }
        }

        $kids = $this->getPartialOffsprings();
        foreach ($kids as $kid) {
            foreach ($kid->reminders as $reminder) {
                $reminders->push($reminder);
            }
        }

        return $reminders;
    }

    /**
     * Get the first progenitor of the contact.
     * @return Contact
     */
    public function getFirstProgenitor()
    {
        $offspring = Offspring::where('contact_id', $this->id)
                        ->first();

        return self::findOrFail($offspring->is_the_child_of);
    }

    /**
     * Get the partner of the contact.
     * @return Contact
     */
    public function getFirstPartner()
    {
        $relationship = Relationship::where('with_contact_id', $this->id)
                        ->first();

        return self::findOrFail($relationship->contact_id);
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
     * Get all the family members.
     * @return Collection
     */
    public function getFamilyMembers()
    {
        $offsprings = $this->offsprings;
        $relationships = $this->activeRelationships;

        $family = collect([]);
        foreach ($offsprings as $offspring) {
            $family->push($offspring->contact);
        }

        foreach ($relationships as $relationship) {
            $family->push($relationship->with_contact);
        }

        return $family;
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
        if (is_null($occasion)) {
            return;
        }

        $specialDate = new SpecialDate;
        $specialDate->createFromDate($year, $month, $day)->setToContact($this);

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
        $specialDate->createFromAge($age)->setToContact($this);

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
        if (is_null($occasion)) {
            return;
        }

        if ($occasion == 'birthdate') {
            if (! $this->birthday_special_date_id) {
                return;
            }

            $this->birthdate->deleteReminder();
            $this->birthdate->delete();

            $this->birthday_special_date_id = null;
            $this->save();
        }

        if ($occasion == 'deceased_date') {
            if (! $this->deceased_special_date_id) {
                return;
            }

            $this->deceasedDate->deleteReminder();
            $this->deceasedDate->delete();

            $this->deceased_special_date_id = null;
            $this->save();
        }

        if ($occasion == 'first_met') {
            if (! $this->first_met_special_date_id) {
                return;
            }

            $this->firstMetDate->deleteReminder();
            $this->firstMetDate->delete();

            $this->first_met_special_date_id = null;
            $this->save();
        }
    }

    /**
     * Gets the contact related to this contact if the current contact is partial.
     */
    public function getRelatedRealContact()
    {
        // Look in the Relationships table
        $relatedContact = \App\Relationship::where('with_contact_id', $this->id)->first();

        if ($relatedContact) {
            return \App\Contact::find($relatedContact->contact_id);
        }

        // Look in the Offspring table
        $relatedContact = \App\Offspring::where('contact_id', $this->id)->first();
        if ($relatedContact) {
            return self::find($relatedContact->is_the_child_of);
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
}
