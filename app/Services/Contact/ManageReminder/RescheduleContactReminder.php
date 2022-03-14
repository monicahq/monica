<?php

namespace App\Services\Contact\ManageReminder;

use Carbon\Carbon;
use App\Services\BaseService;
use App\Models\ContactReminder;
use App\Interfaces\ServiceInterface;
use App\Models\ScheduledContactReminder;

class RescheduleContactReminder extends BaseService implements ServiceInterface
{
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
            'scheduled_contact_reminder_id' => 'required|integer|exists:scheduled_contact_reminders,id',
        ];
    }

    /**
     * Schedule another occurence of the scheduled contact reminder, as the
     * previous iteration has been sent.
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

        $this->scheduledContactReminder = ScheduledContactReminder::with('reminder')
            ->findOrFail($this->data['scheduled_contact_reminder_id']);

        if (! $this->scheduledContactReminder->userNotificationChannel->active) {
            return;
        }

        if ($this->scheduledContactReminder->reminder->type !== ContactReminder::TYPE_ONE_TIME) {
            $this->schedule();
        }
    }

    private function schedule(): void
    {
        if ($this->scheduledContactReminder->reminder->type === ContactReminder::TYPE_RECURRING_DAY) {
            $this->upcomingDate = $this->scheduledContactReminder->scheduled_at->addDay();
        }

        if ($this->scheduledContactReminder->reminder->type === ContactReminder::TYPE_RECURRING_MONTH) {
            $this->upcomingDate = $this->scheduledContactReminder->scheduled_at->addMonth();
        }

        if ($this->scheduledContactReminder->reminder->type === ContactReminder::TYPE_RECURRING_YEAR) {
            $this->upcomingDate = $this->scheduledContactReminder->scheduled_at->addYear();
        }

        ScheduledContactReminder::create([
            'contact_reminder_id' => $this->scheduledContactReminder->contact_reminder_id,
            'user_notification_channel_id' => $this->scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => $this->upcomingDate,
        ]);
    }
}
