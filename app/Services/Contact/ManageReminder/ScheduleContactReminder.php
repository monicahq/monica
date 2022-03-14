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
     * For each user in the vault, a scheduled reminder is created.
     * This service SHOULD NOT BE CALLED FROM THE CLIENTS, ever.
     * It is called by other services.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->getDate();
        $this->schedule();
    }

    /**
     * A ContactReminder can be either a complete date, or only a day/month.
     * If it is only a day/month, we need to add a fake year so we can still
     * manipulate the date as a Carbon object.
     *
     * @return void
     */
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
            $this->upcomingDate->year = Carbon::now()->year;
            if ($this->upcomingDate->isPast()) {
                $this->upcomingDate->year = Carbon::now()->addYear()->year;
            }
        }

        $users = $this->contactReminder->contact->vault->users;

        foreach ($users as $user) {
            // we'll loop through all the user notification channels of this user
            // and schedule the reminder for each of them
            $notificationChannels = $user->notificationChannels;
            foreach ($notificationChannels as $channel) {
                $this->upcomingDate->shiftTimezone($user->timezone);
                $this->upcomingDate->hour = $channel->preferred_time->hour;
                $this->upcomingDate->minute = $channel->preferred_time->minute;

                $this->scheduledContactReminder = ScheduledContactReminder::create([
                    'contact_reminder_id' => $this->contactReminder->id,
                    'user_notification_channel_id' => $channel->id,
                    'scheduled_at' => $this->upcomingDate->tz('UTC'),
                ]);
            }
        }
    }
}
