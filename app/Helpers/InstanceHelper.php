<?php

namespace App\Helpers;

use function Safe\json_decode;
use App\Models\Account\Account;
use App\Models\Instance\Instance;
use App\Models\Settings\Currency;
use Illuminate\Support\Facades\DB;
use function Safe\file_get_contents;

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
     * @param  string $timePeriod  Accepted values: 'monthly', 'annual'
     * @return array|null
     */
    public static function getPlanInformationFromConfig(string $timePeriod): ?array
    {
        if ($timePeriod != 'monthly' && $timePeriod != 'annual') {
            return null;
        }

        $currency = Currency::where('iso', strtoupper(config('cashier.currency')))->first();
        $amount = MoneyHelper::format(config('monica.paid_plan_'.$timePeriod.'_price'), $currency);

        return [
            'type' => $timePeriod,
            'name' => config('monica.paid_plan_'.$timePeriod.'_friendly_name'),
            'id' => config('monica.paid_plan_'.$timePeriod.'_id'),
            'price' => config('monica.paid_plan_'.$timePeriod.'_price'),
            'friendlyPrice' => $amount,
        ];
    }

    /**
     * Get changelogs entries.
     *
     * @param int $limit
     * @return array
     */
    public static function getChangelogEntries($limit = null)
    {
        $json = public_path('changelog.json');
        $changelogs = json_decode(file_get_contents($json), true)['entries'];

        if ($limit) {
            $changelogs = array_slice($changelogs, 0, $limit);
        }

        return $changelogs;
    }

    /**
     * Check if the instance has at least one account.
     *
     * @return bool
     */
    public static function hasAtLeastOneAccount(): bool
    {
        return DB::table('accounts')->count() > 0;
    }
}
