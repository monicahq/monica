<?php

namespace App\Contact\ManageReminders\Services;

use Carbon\Carbon;
use App\Services\BaseService;
use App\Models\ContactReminder;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RescheduleContactReminderForChannel extends BaseService implements ServiceInterface
{
    private UserNotificationChannel $userNotificationChannel;
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
            'user_notification_channel_id' => 'required|integer|exists:user_notification_channels,id',
            'contact_reminder_scheduled_id' => 'required|integer|exists:contact_reminder_scheduled,id',
        ];
    }

    /**
     * Schedule another occurence of the contact reminder, as the
     * previous iteration has been sent, for the given channel.
     * Before sending this occurence, we need to make the user notification
     * channel is still active.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->contactReminder = ContactReminder::findOrFail($this->data['contact_reminder_id']);
        $this->userNotificationChannel = UserNotificationChannel::findOrFail($this->data['user_notification_channel_id']);

        if (! $this->userNotificationChannel->active) {
            throw new ModelNotFoundException('The user notification channel is not active anymore.');
        }

        if ($this->contactReminder->type !== ContactReminder::TYPE_ONE_TIME) {
            $this->schedule();
        }
    }

    private function schedule(): void
    {
        $record = DB::table('contact_reminder_scheduled')
            ->where('id', $this->data['contact_reminder_scheduled_id'])
            ->first();

        $this->upcomingDate = Carbon::createFromFormat('Y-m-d H:i:s', $record->scheduled_at);

        if ($this->contactReminder->type === ContactReminder::TYPE_RECURRING_DAY) {
            $this->upcomingDate = $this->upcomingDate->addDay();
        }

        if ($this->contactReminder->type === ContactReminder::TYPE_RECURRING_MONTH) {
            $this->upcomingDate = $this->upcomingDate->addMonth();
        }

        if ($this->contactReminder->type === ContactReminder::TYPE_RECURRING_YEAR) {
            $this->upcomingDate = $this->upcomingDate->addYear();
        }

        $this->contactReminder->userNotificationChannels()->syncWithoutDetaching([$this->userNotificationChannel->id => [
            'scheduled_at' => $this->upcomingDate,
        ]]);
    }
}
