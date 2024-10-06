<?php

namespace App\Domains\Settings\ManageNotificationChannels\Services;

use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;
use Carbon\Carbon;

class VerifyUserNotificationChannelEmailAddress extends BaseService implements ServiceInterface
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
            'uuid' => 'required|uuid',
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
     * Verify the email address for the given user notification channel.
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->verify();
        $this->rescheduledReminders();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->userNotificationChannel = $this->author->notificationChannels()
            ->findOrFail($this->data['user_notification_channel_id']);
    }

    private function verify(): void
    {
        $this->userNotificationChannel->verified_at = Carbon::now();
        $this->userNotificationChannel->save();
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
