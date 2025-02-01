<?php

namespace App\Domains\Settings\ManageNotificationChannels\Services;

use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;

class ToggleUserNotificationChannel extends BaseService implements ServiceInterface
{
    private array $data;

    private UserNotificationChannel $userNotificationChannel;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'user_notification_channel_id' => 'required|integer|exists:user_notification_channels,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * Mark the given user notification channel active or inactive.
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->toggle();
        $this->updateScheduledReminders();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
        $this->userNotificationChannel = $this->author->notificationChannels()
            ->findOrFail($this->data['user_notification_channel_id']);
    }

    private function toggle(): void
    {
        $this->userNotificationChannel->active = ! $this->userNotificationChannel->active;
        if ($this->userNotificationChannel->active) {
            $this->userNotificationChannel->fails = 0;
        }
        $this->userNotificationChannel->save();
    }

    /**
     * If the notification channel is deactivated, we need to delete all the
     * upcoming scheduled reminders for that channel.
     * If the notification channel is reactivated, we need to reschedule all
     * the contact reminders for this channel specifically.
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
        $this->userNotificationChannel->contactReminders->each->delete();
    }

    private function rescheduledReminders(): void
    {
        (new ScheduleAllContactRemindersForNotificationChannel)->execute([
            'account_id' => $this->data['account_id'],
            'author_id' => $this->data['author_id'],
            'user_notification_channel_id' => $this->userNotificationChannel->id,
        ]);
    }
}
