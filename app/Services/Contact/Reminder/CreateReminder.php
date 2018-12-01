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


namespace App\Services\Contact\Reminder;

use App\Helpers\DateHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;

class CreateReminder extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'required|integer',
            'date' => 'required|date',
            'frequency_type' => [
                'required',
                Rule::in(Reminder::$frequencyTypes),
            ],
            'frequency_number' => 'required|integer',
            'title' => 'string|max:100000',
            'description' => 'nullable|max:1000000',
            'special_date_id' => 'nullable|integer',
        ];
    }

    /**
     * Create a reminder.
     *
     * @param array $data
     * @return Reminder
     */
    public function execute(array $data) : Reminder
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if ($data['special_date_id']) {
            SpecialDate::where('account_id', $data['account_id'])
                ->findOrFail($data['special_date_id']);
        }

        $reminder = $this->attachReminderToLifeEvent($data);

        $reminder->calculateNextExpectedDate()->save();
        $reminder->scheduleNotifications();

        return $reminder;
    }

    /**
     * Actually create the reminder.
     *
     * @return Reminder
     */
    private function attachReminderToLifeEvent(array $data) : Reminder
    {
        $reminder = new Reminder;
        $reminder->frequency_type = $data['frequency_type'];
        $reminder->frequency_number = $data['frequency_number'];
        $reminder->next_expected_date = DateHelper::parseDate($data['date']);
        $reminder->special_date_id = $data['special_date_id'];
        $reminder->account_id = $data['account_id'];
        $reminder->contact_id = $data['contact_id'];
        $reminder->title = $data['title'];
        $reminder->description = $data['description'];
        $reminder->save();

        return $reminder;
    }
}
