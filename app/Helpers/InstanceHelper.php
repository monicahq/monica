<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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

    /**
     * Get changelogs entries.
     *
     * @param int $number
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
}
