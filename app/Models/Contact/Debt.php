<?php

namespace App\Models\Contact;

use App\Traits\HasUuid;
use App\Models\Account\Account;
use App\Traits\AmountFormatter;
use App\Models\Settings\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModelBindingHasherWithContact as Model;

/**
 * @property Account $account
 * @property Contact $contact
 * @property int $amount
 *
 * @method static Builder due()
 * @method static Builder owed()
 * @method static Builder inProgress()
 */
class Debt extends Model
{
    use AmountFormatter, HasUuid;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * Eager load with every debt.
     */
    protected $with = [
        'account',
        'contact',
    ];

    /**
     * Get the account record associated with the debt.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the debt.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the currency record associated with the debt.
     *
     * @return BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Limit results to unpaid/unreceived debt.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'inprogress');
    }

    /**
     * Limit results to due debt.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeDue(Builder $query)
    {
        return $query->where('in_debt', 'yes');
    }

    /**
     * Limit results to owed debt.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOwed(Builder $query)
    {
        return $query->where('in_debt', 'no');
    }
}
