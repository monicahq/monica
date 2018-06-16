<?php

namespace App\Helpers;

use App\Models\Account\Account;

class InstanceHelper
{
    /**
     * Get the number of paid accounts in the instance.
     *
     * @return int
     */
    public static function getNumberOfPaidSubscribers()
    {
        return Account::where('stripe_id', '!=', null)->count();
    }

    /**
     * Get the plan information for the given time period.
     *
     * @param  string Accepted values: 'monthly', 'annual'
     * @return array
     */
    public static function getPlanInformationFromConfig(String $timePeriod)
    {
        if ($timePeriod != 'monthly' && $timePeriod != 'annual') {
            return;
        }

        return [
            'type' => $timePeriod,
            'name' => config('monica.paid_plan_'.$timePeriod.'_friendly_name'),
            'id' => config('monica.paid_plan_'.$timePeriod.'_id'),
            'price' => config('monica.paid_plan_'.$timePeriod.'_price'),
            'friendlyPrice' => config('monica.paid_plan_'.$timePeriod.'_price') / 100,
        ];
    }
}
