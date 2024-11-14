<?php

namespace App\Domains\Settings\ManageNotificationChannels\Services;

use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;
use App\Notifications\ReminderTriggered;
use App\Services\BaseService;
use Exception;
use Illuminate\Support\Facades\Notification;

/**
 * Inspired by https://abstractentropy.com/laravel-notifications-telegram-bot/.
 */
class SendTestTelegramNotification extends BaseService implements ServiceInterface
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
     * Send a test notification to Telegram.
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->send();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->userNotificationChannel = $this->author->notificationChannels()
            ->findOrFail($this->data['user_notification_channel_id']);

        if ($this->userNotificationChannel->type !== UserNotificationChannel::TYPE_TELEGRAM) {
            throw new Exception('Only telegram messages can be sent.');
        }
    }

    private function send(): void
    {
        $content = trans('This is a test notification for :name', ['name' => $this->author->name]);

        Notification::route('telegram', $this->userNotificationChannel->content)
            ->notify((new ReminderTriggered($this->userNotificationChannel, $content, 'Test'))->locale($this->userNotificationChannel->user->locale));
    }
}
