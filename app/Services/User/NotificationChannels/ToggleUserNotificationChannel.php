<?php

namespace App\Services\User\NotificationChannels;

use App\Models\User;
use App\Jobs\CreateAuditLog;
use App\Services\BaseService;
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
