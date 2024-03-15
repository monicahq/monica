<?php

namespace App\Domains\Contact\ManageReminders\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactReminder;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScheduleContactReminderForUser extends BaseService implements ServiceInterface
{
    private ContactReminder $contactReminder;

    private array $data;

    private Carbon $upcomingDate;

    private User $user;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'contact_reminder_id' => 'required|integer|exists:contact_reminders,id',
            'user_id' => 'required|uuid|exists:users,id',
        ];
    }

    /**
     * Schedule a contact reminder for the given user, on his timezone.
     * For each user in the vault, a scheduled reminder is created.
     * This service SHOULD NOT BE CALLED FROM THE CLIENTS, ever.
     * It is called by other services.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->contactReminder = ContactReminder::findOrFail($this->data['contact_reminder_id']);
        $this->user = User::findOrFail($this->data['user_id']);
        if ($this->user->account_id != $this->contactReminder->contact->vault->account_id) {
            throw new ModelNotFoundException('The user does not belong to the vault\'s account.');
        }

        $this->getDate();
        $this->schedule();
    }

    /**
     * A ContactReminder can be either a complete date, or only a day/month.
     * If it is only a day/month, we need to add a fake year so we can still
     * manipulate the date as a Carbon object.
     */
    private function getDate(): void
    {
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

        $notificationChannels = $this->user->notificationChannels;
        foreach ($notificationChannels as $channel) {
            $this->upcomingDate->shiftTimezone($this->user->timezone ?? config('app.timezone'));
            $this->upcomingDate->hour = $channel->preferred_time->hour;
            $this->upcomingDate->minute = $channel->preferred_time->minute;

            $this->contactReminder->userNotificationChannels()->syncWithoutDetaching([$channel->id => [
                'scheduled_at' => $this->upcomingDate->tz('UTC'),
            ]]);
        }
    }
}
