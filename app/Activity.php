<?php

namespace App;

use Parsedown;
use Carbon\Carbon;
use App\Traits\Hasher;
use App\Traits\Journalable;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\IsJournalableInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

/**
 * @property Account $account
 * @property Contact $contact
 * @property ActivityType $type
 */
class Activity extends Model implements IsJournalableInterface
{
    use Journalable;
    use Hasher;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activities';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date_it_happened'];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['type'];

    /**
     * Get the account record associated with the gift.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the gift.
     *
     * @return BelongsTo
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Get the activity type record associated with the activity.
     *
     * @return BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    /**
     * Get all of the activities journal entries.
     */
    public function journalEntries()
    {
        return $this->morphMany('App\JournalEntry', 'journalable');
    }

    /**
     * Return the markdown parsed body.
     *
     * @return string
     */
    public function getParsedContentAttribute()
    {
        if (is_null($this->description)) {
            return;
        }

        return (new Parsedown())->text($this->description);
    }

    /**
     * Get the date_it_happened field according to user's timezone.
     *
     * @param string $value
     * @return string
     */
    public function getDateItHappenedAttribute($value)
    {
        if (auth()->user()) {
            return Carbon::parse($value, auth()->user()->timezone);
        }

        return Carbon::parse($value);
    }

    /**
     * Get the summary for this activity.
     *
     * @return string or null
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Get the description for this activity.
     *
     * @return string or null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the date the activity happened.
     *
     * @return Carbon
     */
    public function getDateItHappened()
    {
        return $this->date_it_happened;
    }

    /**
     * Get the key of the title of the activity.
     *
     * @return string or null
     */
    public function getTitle()
    {
        return $this->type ? $this->type->key : null;
    }

    /**
     * Get all the contacts this activity is associated with.
     */
    public function getContactsForAPI()
    {
        $attendees = collect([]);

        foreach ($this->contacts as $contact) {
            $attendee = Contact::find($contact->id);
            $attendees->push(new ContactShortResource($attendee));
        }

        return $attendees;
    }

    /**
     * Gets the information about the activity for the journal.
     * @return array
     */
    public function getInfoForJournalEntry()
    {
        return [
            'type' => 'activity',
            'id' => $this->id,
            'activity_type' => (! is_null($this->type) ? $this->type->getTranslationKeyAsString() : null),
            'summary' => $this->summary,
            'description' => $this->description,
            'day' => $this->date_it_happened->day,
            'day_name' => ucfirst(\App\Helpers\DateHelper::getShortDay($this->date_it_happened)),
            'month' => $this->date_it_happened->month,
            'month_name' => strtoupper(\App\Helpers\DateHelper::getShortMonth($this->date_it_happened)),
            'year' => $this->date_it_happened->year,
            'attendees' => $this->getContactsForAPI(),
        ];
    }
}
