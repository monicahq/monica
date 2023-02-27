<?php

namespace App\Domains\Contact\ManageReminders\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RescheduleContactReminderForChannel extends BaseService implements ServiceInterface
{
    private UserNotificationChannel $userNotificationChannel;

    private ContactReminder $contactReminder;

    private Carbon $upcomingDate;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
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
        } else {
            DB::table('contact_reminder_scheduled')
                ->where('id', $this->data['contact_reminder_scheduled_id'])
                ->delete();
        }
    }

    private function schedule(): void
    {
        $record = DB::table('contact_reminder_scheduled')
            ->where('id', $this->data['contact_reminder_scheduled_id'])
            ->first();

        $this->upcomingDate = Carbon::createFromFormat('Y-m-d H:i:s', $record->scheduled_at);

        switch ($this->contactReminder->type) {
            case ContactReminder::TYPE_RECURRING_DAY:
                $this->upcomingDate = $this->upcomingDate->addDay();
                break;
            case ContactReminder::TYPE_RECURRING_MONTH:
                $this->upcomingDate = $this->upcomingDate->addMonth();
                break;
            case ContactReminder::TYPE_RECURRING_YEAR:
                $this->upcomingDate = $this->upcomingDate->addYear();
                break;
            default:
                throw new \Exception('Invalid contact reminder type.');
        }

        $this->contactReminder->userNotificationChannels()->syncWithoutDetaching([$this->userNotificationChannel->id => [
            'scheduled_at' => $this->upcomingDate,
        ]]);
    }
}
