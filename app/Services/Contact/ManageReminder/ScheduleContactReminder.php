<?php

namespace App\Services\Contact\ManageReminder;

use Carbon\Carbon;
use App\Services\BaseService;
use App\Models\ContactReminder;
use App\Interfaces\ServiceInterface;
use App\Models\ScheduledContactReminder;

class ScheduleContactReminder extends BaseService implements ServiceInterface
{
    private ContactReminder $contactReminder;
    private ScheduledContactReminder $scheduledContactReminder;
    private array $data;
    private Carbon $upcomingDate;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'contact_reminder_id' => 'required|integer|exists:contact_reminders,id',
        ];
    }

    /**
     * Schedule a contact reminder.
     * This service SHOULD NOT BE CALLED FROM THE CLIENTS, ever.
     * It is called by other services.
     * For each user in the vault, a scheduled reminder is created.
     *
     * @param  array  $data
     * @return ScheduledContactReminder
     */
    public function execute(array $data): ScheduledContactReminder
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->getDate();
        $this->schedule();

        return $this->scheduledContactReminder;
    }

    private function getDate(): void
    {
        $this->contactReminder = ContactReminder::findOrFail($this->data['contact_reminder_id']);

        if (! $this->contactReminder->year) {
            $this->upcomingDate = Carbon::parse('1900-'.$this->contactReminder->month.'-'.$this->contactReminder->day);
        } else {
            $this->upcomingDate = Carbon::parse($this->contactReminder->year.'-'.$this->contactReminder->month.'-'.$this->contactReminder->day);
        }
    }

    private function schedule(): void
    {
        // is the date in the past? if so, we need to schedule the reminder
        // for next year
        if ($this->upcomingDate->isPast()) {
            $this->upcomingDate->year = Carbon::now()->addYear()->year;
        }

        $this->scheduledContactReminder = ScheduledContactReminder::create([
            'contact_id' => $this->contactReminder->id,
            'contact_reminder_id' => $this->contactReminder->id,
            'triggered_at' => $this->upcomingDate,
        ]);
    }
}
