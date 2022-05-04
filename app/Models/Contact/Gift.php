<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Models\Account\Photo;
use App\Models\Account\Account;
use App\Traits\AmountFormatter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingWithContact as Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property Account $account
 * @property Contact $contact
 * @property Contact|null $recipient
 * @property string $name
 * @property string $comment
 * @property string $url
 * @property Contact $is_for
 *
 * @method static Builder offered()
 * @method static Builder isIdea()
 */
class Gift extends Model
{
    use AmountFormatter, HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
    ];

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
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact record associated with the gift.
     *
     * @return HasOne
     */
    public function recipient()
    {
        return $this->hasOne(Contact::class, 'id', 'is_for');
    }

    /**
     * Get the photos record associated with the gift.
     *
     * @return BelongsToMany
     */
    public function photos()
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * Limit results to already offered gifts.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOffered(Builder $query)
    {
        return $query->where('status', 'offered');
    }

    /**
     * Limit results to gifts at the idea stage.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsIdea(Builder $query)
    {
        return $query->where('status', 'idea');
    }

    /**
     * Check whether the gift is meant for a particular member
     * of the contact's family.
     *
     * @return bool
     */
    public function hasParticularRecipient()
    {
        return $this->is_for !== null && $this->is_for !== 0;
    }

    /**
     * Set the recipient for the gift.
     *
     * @param  int  $value
     * @return void
     */
    public function setRecipientAttribute($value): void
    {
        $this->attributes['is_for'] = $value;
    }

    /**
     * Get the name of the recipient for this gift.
     *
     * @return string|null
     */
    public function getRecipientNameAttribute(): ?string
    {
        if ($this->hasParticularRecipient()) {
            $recipient = $this->recipient;
            if (! is_null($recipient)) {
                return $recipient->first_name;
            }
        }

        return null;
    }
}
