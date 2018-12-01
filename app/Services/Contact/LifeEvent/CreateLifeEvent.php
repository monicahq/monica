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

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;

class CreateLifeEvent extends BaseService
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
            'life_event_type_id' => 'required|integer',
            'happened_at' => 'required|date',
            'name' => 'nullable|string',
            'note' => 'nullable|string',
            'has_reminder' => 'required|boolean',
            'happened_at_month_unknown' => 'required|boolean',
            'happened_at_day_unknown' => 'required|boolean',
        ];
    }

    /**
     * Create a life event.
     *
     * @param array $data
     * @return LifeEvent
     */
    public function execute(array $data) : LifeEvent
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        LifeEventType::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_type_id']);

        $lifeEvent = new LifeEvent;
        $lifeEvent->account_id = $data['account_id'];
        $lifeEvent->contact_id = $data['contact_id'];
        $lifeEvent->life_event_type_id = $data['life_event_type_id'];
        $lifeEvent->happened_at = $data['happened_at'];
        $lifeEvent->name = $data['name'];
        $lifeEvent->note = $data['note'];
        $lifeEvent->happened_at_month_unknown = $data['happened_at_month_unknown'];
        $lifeEvent->happened_at_day_unknown = $data['happened_at_day_unknown'];
        $lifeEvent->save();

        $this->addYearlyReminder($data, $lifeEvent);

        // Get the newly created object as the Create method doesn't return all
        // fields by default
        return LifeEvent::find($lifeEvent->id);
    }

    /**
     * Add yearly reminder if necessary.
     *
     * @param array $data
     * @param LifeEvent $lifeEvent
     */
    private function addYearlyReminder($data, $lifeEvent)
    {
        if ($data['has_reminder']) {
            $array = [
                'account_id' => $data['account_id'],
                'life_event_id' => $lifeEvent->id,
                'date' => $data['happened_at'],
                'frequency_type' => 'year',
                'frequency_number' => 1,
            ];

            $reminder = (new AddReminderToLifeEvent)->execute($array);

            $lifeEvent->reminder_id = $reminder->id;
            $lifeEvent->save();
        }
    }
}
