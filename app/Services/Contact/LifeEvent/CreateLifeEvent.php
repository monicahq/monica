<?php

namespace App\Services\Contact\LifeEvent;

use Carbon\Carbon;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use App\Services\Contact\Reminder\CreateReminder;

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
     * @param  array  $data
     * @return LifeEvent
     */
    public function execute(array $data): LifeEvent
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

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
     * @param  array  $data
     * @param  LifeEvent  $lifeEvent
     */
    private function addYearlyReminder($data, $lifeEvent)
    {
        if ($data['has_reminder']) {
            $date = Carbon::parse($data['happened_at']);

            $data = [
                'contact_id' => $data['contact_id'],
                'account_id' => $data['account_id'],
                'initial_date' => $date->toDateString(),
                'frequency_type' => 'year',
                'frequency_number' => 1,
                'title' => $lifeEvent->lifeEventType->name,
                'description' => null,
            ];

            $reminder = app(CreateReminder::class)->execute($data);

            $lifeEvent->reminder_id = $reminder->id;
            $lifeEvent->save();
        }
    }
}
