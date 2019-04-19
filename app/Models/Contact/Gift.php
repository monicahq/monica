<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ModelBindingWithContact as Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $contact
 * @property Contact $recipient
 * @method static Builder offered()
 * @method static Builder isIdea()
 */
class Gift extends Model
{
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
    protected $dates = [
        'offered_at',
        'received_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_an_idea' => 'boolean',
        'has_been_offered' => 'boolean',
        'has_been_received' => 'boolean',
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
     * Limit results to already offered gifts.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOffered(Builder $query)
    {
        return $query->where('has_been_offered', 1);
    }

    /**
     * Limit results to gifts at the idea stage.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsIdea(Builder $query)
    {
        return $query->where('is_an_idea', 1);
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
     * @param int $value
     *
     * @return void
     */
    public function setRecipientAttribute($value): void
    {
        $this->attributes['is_for'] = $value;
    }

    /**
     * Get the name of the recipient for this gift.
     *
     * @return string
     */
    public function getRecipientNameAttribute()
    {
        if ($this->hasParticularRecipient()) {
            $recipient = $this->recipient;
            if (! is_null($recipient)) {
                return $this->recipient->first_name;
            }
        }
    }

    /**
     * Toggle a gift between the idea and offered state.
     * @return void
     */
    public function toggle()
    {
        $this->has_been_received = false;

        if ($this->is_an_idea == 1) {
            $this->is_an_idea = false;
            $this->has_been_offered = true;
            $this->save();

            return;
        }

        $this->is_an_idea = true;
        $this->has_been_offered = false;
        $this->save();
    }
}
