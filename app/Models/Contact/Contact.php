<?php

namespace App\Models\Contact;

use Carbon\Carbon;
use App\Helpers\DBHelper;
use App\Models\User\User;
use App\Traits\Searchable;
use Illuminate\Support\Str;
use App\Helpers\LocaleHelper;
use App\Models\Account\Photo;
use App\Models\Journal\Entry;
use function Safe\preg_split;
use App\Helpers\WeatherHelper;
use App\Models\Account\Account;
use App\Models\Account\Weather;
use App\Models\Account\Activity;
use function Safe\preg_match_all;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use App\Models\Account\ActivityStatistic;
use App\Models\Relationship\Relationship;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingHasher as Model;
use App\Http\Resources\Tag\Tag as TagResource;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Http\Resources\Address\Address as AddressResource;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\ContactField\ContactField as ContactFieldResource;

class Contact extends Model
{
    use Searchable;

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
        'description',
        'job',
    ];

    // The list of columns we want the Searchable trait to select.
    protected $return_from_search = [
        'id',
        'first_name',
        'middle_name',
        'last_name',
        'nickname',
        'gender_id',
        'account_id',
        'created_at',
        'updated_at',
        'is_partial',
        'is_starred',
        'avatar_source',
        'avatar_adorable_url',
        'avatar_gravatar_url',
        'avatar_default_url',
        'avatar_photo_id',
        'default_avatar_color',
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
        'description',
        'account_id',
        'is_partial',
        'job',
        'company',
        'food_preferences',
        'birthday_reminder_id',
        'birthday_special_date_id',
        'is_dead',
        'last_consulted_at',
        'created_at',
        'first_met_additional_info',
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
        'avatarPhoto',
        'gender',
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
        'is_starred' => 'boolean',
        'is_active' => 'boolean',
        'stay_in_touch_frequency' => 'integer',
    ];

    /**
     * The name order attribute that indicates how to format the name of the
     * contact.
     *
     * @var string
     */
    protected $nameOrder = 'firstname_lastname';

    /**
     * Get Searchable Fields.
     *
     * @return array
     */
    public function getSearchableFields()
    {
        return $this->searchable_columns;
    }

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
     * @return BelongsTo
     */
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Get the activity records associated with the contact.
     *
     * @return BelongsToMany
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
        return $this->hasMany(ActivityStatistic::class)->orderBy('year', 'desc');
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
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get only the active reminder records associated with the contact.
     *
     * @return HasMany
     */
    public function activeReminders()
    {
        return $this->hasMany(Reminder::class)->active();
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
     * @return BelongsToMany
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
     * Get the Conversation records associated with the contact.
     *
     * @return HasMany
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class)->orderBy('conversations.happened_at', 'desc');
    }

    /**
     * Get the Message records associated with the contact.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the Document records associated with the contact.
     *
     * @return HasMany
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the Photo records associated with the contact.
     *
     * @return BelongsToMany
     */
    public function photos()
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * Get the Life event records associated with the contact.
     *
     * @return HasMany
     */
    public function lifeEvents()
    {
        return $this->hasMany(LifeEvent::class)->orderBy('life_events.happened_at', 'desc');
    }

    /**
     * Get the Occupation records associated with the contact.
     *
     * @return HasMany
     */
    public function occupations()
    {
        return $this->hasMany(Occupation::class);
    }

    /**
     * Get the Avatar Photo records associated with the contact.
     *
     * @return HasOne
     */
    public function avatarPhoto()
    {
        return $this->hasOne(Photo::class, 'id', 'avatar_photo_id');
    }

    /**
     * Test if this is the 'me' contact.
     *
     * @return bool
     */
    public function isMe()
    {
        return $this->id == auth()->user()->me_contact_id;
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
                $builder->select(['*', 'contacts.id as id']);

                return $builder;
            case 'lastactivitydateOldtoNew':
                $builder->leftJoin('activity_contact', 'contacts.id', '=', 'activity_contact.contact_id');
                $builder->leftJoin('activities', 'activity_contact.activity_id', '=', 'activities.id');
                $builder->orderBy('activities.date_it_happened', 'asc');
                $builder->select(['*', 'contacts.id as id']);

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
     * Scope a query to only include contacts who are active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope a query to only include contacts who are alive.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAlive($query)
    {
        return $query->where('is_dead', 0);
    }

    /**
     * Scope a query to only include contacts who are dead.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDead($query)
    {
        return $query->where('is_dead', 1);
    }

    /**
     * Scope a query to only include contacts who are not active.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotActive($query)
    {
        return $query->where('is_active', 0);
    }

    /**
     * Mutator first_name.
     * Get the first name of the contact.
     *
     * @param string|null $value
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = trim($value);
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
        $name = Str::ascii($this->name, LocaleHelper::getLang());
        preg_match_all('/(?<=\s|^)[a-zA-Z0-9]/i', $name, $initials);

        return implode('', $initials[0]);
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
            $incompleteName .= ' '.mb_substr($this->last_name, 0, 1);
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
     * @return \DateTime|null
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
     * Get all the contacts related to the current contact by a specific
     * relationship type group.
     *
     * @param  string $type
     * @return Collection|null
     */
    public function getRelationshipsByRelationshipTypeGroup(string $type)
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
                    'name' => $relationship->relationshipType->name,
                ],
                'contact' => new ContactShortResource($contact),
                ]);
        }

        return $contacts;
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
     * Set the name of the contact.
     *
     * @param  string $firstName
     * @param  string $middleName
     * @param  string $lastName
     * @return bool
     */
    public function setName(string $firstName, string $lastName = null, string $middleName = null)
    {
        if ($firstName === '') {
            return false;
        }

        $this->first_name = $firstName;
        $this->middle_name = $middleName;
        $this->last_name = $lastName;

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
     * @param  string $foodPreferences
     * @return void
     */
    public function updateFoodPreferences($foodPreferences)
    {
        if ($foodPreferences == '') {
            $this->food_preferences = null;
        } else {
            $this->food_preferences = $foodPreferences;
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
     * Get the default avatar URL.
     *
     * @return string
     */
    public function getAvatarDefaultURL()
    {
        if (empty($this->avatar_default_url)) {
            return '';
        }

        try {
            $matches = preg_split('/\?/', $this->avatar_default_url);
            $url = asset(Storage::disk(config('filesystems.default'))->url($matches[0]));
            if (count($matches) > 1) {
                $url .= '?'.$matches[1];
            }

            return $url;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Returns the URL of the avatar, properly sized.
     * The avatar can come from 4 sources:
     *  - default,
     *  - Adorable avatar,
     *  - Gravatar
     *  - or a photo that has been uploaded.
     *
     * @return string|null
     */
    public function getAvatarURL()
    {
        $avatarURL = '';

        switch ($this->avatar_source) {
            case 'adorable':
                $avatarURL = $this->avatar_adorable_url;
                break;
            case 'gravatar':
                $avatarURL = $this->avatar_gravatar_url;
                break;
            case 'photo':
                $avatarURL = $this->avatarPhoto->url();
                break;
            case 'default':
            default:
                $avatarURL = $this->getAvatarDefaultURL();
                break;
        }

        return $avatarURL;
    }

    /**
     * Delete avatars files.
     * This does not touch avatar_location or avatar_file_name properties of the contact.
     */
    public function deleteAvatars()
    {
        if (! $this->has_avatar || $this->avatar_location == 'external') {
            return;
        }

        $storage = Storage::disk($this->avatar_location);
        $this->deleteAvatarSize($storage);
        $this->deleteAvatarSize($storage, 110);
        $this->deleteAvatarSize($storage, 174);
    }

    /**
     * Delete avatar file for one size.
     *
     * @param Filesystem $storage
     * @param int $size
     */
    private function deleteAvatarSize(Filesystem $storage, int $size = null)
    {
        $avatarFileName = $this->avatar_file_name;

        if (! is_null($size)) {
            $filename = pathinfo($avatarFileName, PATHINFO_FILENAME);
            $extension = pathinfo($avatarFileName, PATHINFO_EXTENSION);
            $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        try {
            if ($storage->exists($avatarFileName)) {
                $storage->delete($avatarFileName);
            }
        } catch (FileNotFoundException $e) {
            return;
        }
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
        return AddressResource::collection($this->addresses);
    }

    /**
     * Get the list of contact fields for this contact.
     */
    public function getContactFieldsForAPI()
    {
        return ContactFieldResource::collection($this->contactFields);
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
     *
     * @return Contact|null
     */
    public function getIntroducer()
    {
        if (! $this->first_met_through_contact_id) {
            return;
        }

        try {
            /** @var Contact $contact */
            $contact = self::where('account_id', $this->account_id)
                ->findOrFail($this->first_met_through_contact_id);
        } catch (ModelNotFoundException $e) {
            return;
        }

        return $contact;
    }

    /**
     * Sets a Special Date for this contact, for a specific occasion (birthday,
     * decease date,...) of which we know the date.
     *
     * @param string $occasion
     * @param int $year
     * @param int $month
     * @param int $day
     * @return SpecialDate|null
     */
    public function setSpecialDate($occasion, int $year, int $month, int $day)
    {
        if (empty($occasion)) {
            return;
        }

        $specialDate = new SpecialDate;
        $specialDate->setToContact($this)->createFromDate($year, $month, $day);

        switch ($occasion) {
            case 'birthdate':
                $this->birthday_special_date_id = $specialDate->id;
                break;
            case 'deceased_date':
                $this->deceased_special_date_id = $specialDate->id;
                break;
            case 'first_met':
                $this->first_met_special_date_id = $specialDate->id;
                break;
            default:
                break;
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

        switch ($occasion) {
            case 'birthdate':
                $this->birthday_special_date_id = $specialDate->id;
                break;
            case 'deceased_date':
                $this->deceased_special_date_id = $specialDate->id;
                break;
            case 'first_met':
                $this->first_met_special_date_id = $specialDate->id;
                break;
            default:
                break;
        }

        $this->save();

        return $specialDate;
    }

    /**
     * Delete all related objects.
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
        $relationships = $this->relationships->filter(function ($item) {
            return ! is_null($item->ofContact) &&
                   ! is_null($item->ofContact->birthday_special_date_id);
        });

        $reminders = collect();
        foreach ($relationships as $relationship) {
            $reminder = Reminder::where('account_id', $this->account_id)
                ->find($relationship->ofContact->birthday_reminder_id);

            if ($reminder) {
                $reminders->push($reminder);
            }
        }

        return $reminders;
    }

    /**
     * Gets the first contact related to this contact if the current contact is
     * partial.
     *
     * @return self|null
     */
    public function getRelatedRealContact()
    {
        $contact = $this;

        return self::setEagerLoads([])->where('account_id', $this->account_id)
            ->where('id', function ($query) use ($contact) {
                $query->select('of_contact')
                        ->from('relationships')
                        ->where([
                            'account_id' => $contact->account_id,
                            'contact_is' => $contact->id,
                        ]);
            })
            ->first();
    }

    /**
     * Get the link to this contact, or the related real contact.
     * @return string
     */
    public function getLink()
    {
        $contact = $this->is_partial ? $this->getRelatedRealContact() : $this;
        if (is_null($contact)) {
            $contact = $this;
        }

        return route('people.show', $contact);
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
     * @return int|null
     */
    public function getAgeAtDeath()
    {
        if (! $this->deceasedDate) {
            return;
        }

        if ($this->deceasedDate->is_year_unknown == 1) {
            return;
        }

        if (! $this->birthdate) {
            return;
        }

        return $this->birthdate->date->diffInYears($this->deceasedDate->date);
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
     * @param Carbon|null $triggerDate
     */
    public function setStayInTouchTriggerDate($frequency, $triggerDate = null)
    {
        // prevent timestamp update
        $timestamps = $this->timestamps;
        $this->timestamps = false;

        if ($frequency == 0) {
            $this->stay_in_touch_trigger_date = null;
        } else {
            $triggerDate = $triggerDate ?? now();
            $newTriggerDate = $triggerDate->addDays($frequency);
            $this->stay_in_touch_trigger_date = $newTriggerDate;
        }

        $this->save();

        $this->timestamps = $timestamps;
    }

    /**
     * Get the weather information for this contact, based on the first address
     * on the profile.
     *
     * @return Weather
     */
    public function getWeather()
    {
        return WeatherHelper::getWeatherForAddress($this->addresses()->first());
    }

    public function updateConsulted()
    {
        // prevent timestamp update
        $timestamps = $this->timestamps;
        $this->timestamps = false;

        $this->last_consulted_at = now();
        $this->number_of_views = $this->number_of_views + 1;

        $this->save();

        $this->timestamps = $timestamps;
    }
}
