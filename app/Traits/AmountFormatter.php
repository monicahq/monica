<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use App\Helpers\MoneyHelper;
use App\Models\Settings\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait AmountFormatter
{
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
     * Set exchange value.
     *
     * @return void
     */
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = MoneyHelper::parseInput($value, $this->currency);
    }

    /**
     * Get exchange value.
     *
     * @return string|null
     */
    public function getAmountAttribute(): ?string
    {
        if (! ($amount = Arr::get($this->attributes, 'amount', null))) {
            return null;
        }

        return MoneyHelper::exchangeValue($amount, $this->currency);
    }

    /**
     * Get value of amount (without currency).
     *
     * @return string
     */
    public function getValueAttribute(): string
    {
        if (! ($amount = Arr::get($this->attributes, 'amount', null))) {
            return '';
        }

        return MoneyHelper::getValue($amount, $this->currency);
    }

    /**
     * Get display value: amount with currency.
     *
     * @return string
     */
    public function getDisplayValueAttribute(): string
    {
        if (! ($amount = Arr::get($this->attributes, 'amount', null))) {
            return '';
        }

        return MoneyHelper::format($amount, $this->currency);
    }
}
