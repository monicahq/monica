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


namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Contact\ReminderRule;

class ReminderRulesController extends Controller
{
    /**
     * Get all the reminder rules.
     */
    public function index()
    {
        $reminderRulesData = collect([]);
        $reminderRules = auth()->user()->account->reminderRules;

        foreach ($reminderRules as $reminderRule) {
            $data = [
                'id' => $reminderRule->id,
                'number_of_days_before' => $reminderRule->number_of_days_before,
                'active' => $reminderRule->active,
            ];
            $reminderRulesData->push($data);
        }

        return $reminderRulesData;
    }

    public function toggle(Request $request, ReminderRule $reminderRule)
    {
        $reminderRule->toggle();

        return trans('settings.personalization_reminder_rule_save');
    }
}
