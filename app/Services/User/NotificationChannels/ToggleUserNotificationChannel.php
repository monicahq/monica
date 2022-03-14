<?php

namespace App\Services\User\NotificationChannels;

use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;

class ToggleUserNotificationChannel extends BaseService implements ServiceInterface
{
    private array $data;
    private UserNotificationChannel $userNotificationChannel;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'user_notification_channel_id' => 'required|integer|exists:user_notification_channels,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * Mark the given user notification channel active or inactive.
     *
     * @param  array  $data
     * @return UserNotificationChannel
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->toggle();
        $this->updateScheduledReminders();
        $this->log();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->userNotificationChannel = UserNotificationChannel::where('user_id', $this->data['author_id'])
            ->findOrFail($this->data['user_notification_channel_id']);
    }

    private function toggle(): void
    {
        $this->userNotificationChannel->active = ! $this->userNotificationChannel->active;
        $this->userNotificationChannel->save();
    }

    /**
     * If the notification channel is deactivated, we need to delete all the
     * upcoming scheduled reminders for that channel.
     * If the notification channel is reactivated, we need to reschedule all
     * the contact reminders for this channel specifically.
     *
     * @return void
     */
    private function updateScheduledReminders(): void
    {
        if ($this->userNotificationChannel->active) {
            $this->rescheduledReminders();
        } else {
            $this->deleteScheduledReminders();
        }
    }

    private function deleteScheduledReminders(): void
    {
        DB::table('scheduled_contact_reminders')
            ->where('user_notification_channel_id', $this->userNotificationChannel->id)
            ->delete();
    }

    private function rescheduledReminders(): void
    {
        (new ScheduleAllContactRemindersForNotificationChannel)->execute([
            'account_id' => $this->data['account_id'],
            'author_id' => $this->data['author_id'],
            'user_notification_channel_id' => $this->userNotificationChannel->id,
        ]);
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'user_notification_channel_toggled',
            'objects' => json_encode([
                'label' => $this->userNotificationChannel->label,
                'type' => $this->userNotificationChannel->type,
            ]),
        ])->onQueue('low');
    }
}
