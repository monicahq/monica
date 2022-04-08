<?php

namespace App\Settings\ManageNotificationChannels\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;

class VerifyUserNotificationChannelEmailAddress extends BaseService implements ServiceInterface
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
            'uuid' => 'required|uuid',
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
     * Verify the email address for the given user notification channel.
     *
     * @param  array  $data
     * @return UserNotificationChannel
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->verify();
        $this->rescheduledReminders();
        $this->log();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->userNotificationChannel = UserNotificationChannel::where('user_id', $this->data['author_id'])
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

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'user_notification_channel_verified',
            'objects' => json_encode([
                'label' => $this->userNotificationChannel->label,
                'type' => $this->userNotificationChannel->type,
            ]),
        ])->onQueue('low');
    }
}
