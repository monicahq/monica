<?php

namespace App\Helpers;

use App\Account;

class InstanceHelper
{
    /**
     * Get the number of paid accounts in the instance.
     *
     * @return int
     */
    public static function getNumberOfPaidSubscribers()
    {
        $paidAccounts = Account::where('stripe_id', '!=', NULL)->count();

        return $paidAccounts;
    }

    /**
     * Get the plan information for the given time period.
     *
     * @param  String Accepted values: 'monthly', 'annual'
     * @return Array
     */
    public static function getPlanInformationFromConfig(String $timePeriod)
    {
        if ($timePeriod != 'monthly' && $timePeriod != 'annual') {
            return;
        }

        if ($timePeriod == 'monthly') {
            $planInformation = [
                'type' => 'monthly',
                'name' => config('monica.paid_plan_friendly_name'),
                'id' => config('monica.paid_plan_id'),
                'price' => config('monica.paid_plan_price'),
                'friendlyPrice' => config('monica.paid_plan_price')/100,
            ];
        }

        if ($timePeriod == 'annual') {
            $planInformation = [
                'type' => 'annual',
                'name' => config('monica.paid_plan_annual_friendly_name'),
                'id' => config('monica.paid_plan_annual_id'),
                'price' => config('monica.paid_plan_annual_price'),
                'friendlyPrice' => config('monica.paid_plan_annual_price')/100,
            ];
        }

        return $planInformation;
    }
}
