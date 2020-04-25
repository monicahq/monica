<?php

namespace App\Models\Contact;

use App\Helpers\MoneyHelper;
use App\Models\Account\Account;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ModelBindingHasherWithContact as Model;

/**
 * @property Account $account
 * @property Contact $contact
 * @property int $amount
 * @method static Builder due()
 * @method static Builder owed()
 * @method static Builder inProgress()
 */
class Debt extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
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
     * @param Builder $query
     * @return Builder
     */
    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'inprogress');
    }

    /**
     * Limit results to due debt.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDue(Builder $query)
    {
        return $query->where('in_debt', 'yes');
    }

    /**
     * Limit results to owed debt.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOwed(Builder $query)
    {
        return $query->where('in_debt', 'no');
    }

    /**
     * Set exchange value.
     *
     * @return string
     */
    public function setAmountAttribute($value)
    {
        $currency = $this->currency()->first();
        if (! $currency) {
            $currency = Auth::user()->currency;
        }
        $this->attributes['amount'] = MoneyHelper::formatInput($value, $currency);
    }

    /**
     * Get value of amount (without currency).
     *
     * @return string
     */
    public function getAmountAttribute(): string
    {
        if (! $this->attributes['amount']) {
            return '';
        }
        $currency = $this->currency()->first();
        if (! $currency) {
            $currency = Auth::user()->currency;
        }

        return MoneyHelper::exchangeValue($this->attributes['amount'], $currency);
    }

    /**
     * Get display value: amount with currency.
     *
     * @return string
     */
    public function getDisplayValueAttribute(): string
    {
        if (! $this->attributes['amount']) {
            return '';
        }
        $currency = $this->currency()->first();
        if (! $currency) {
            $currency = Auth::user()->currency;
        }

        return MoneyHelper::format($this->attributes['amount'], $currency);
    }
}
