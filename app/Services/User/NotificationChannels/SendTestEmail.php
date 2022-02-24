<?php

namespace App\Services\User\NotificationChannels;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\TestEmailSent;
use App\Services\BaseService;
use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationSent;
use Illuminate\Support\Facades\Mail;
use App\Models\UserNotificationChannel;

class SendTestEmail extends BaseService implements ServiceInterface
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
     * Send a test email.
     *
     * @param  array  $data
     * @return UserNotificationChannel
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->send();
        $this->log();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->userNotificationChannel = UserNotificationChannel::where('user_id', $this->data['author_id'])
            ->findOrFail($this->data['user_notification_channel_id']);

        if ($this->userNotificationChannel->type !== UserNotificationChannel::TYPE_EMAIL) {
            throw new Exception('Only email can be sent.');
        }
    }

    private function send(): void
    {
        Mail::to($this->userNotificationChannel->content)->send(
            new TestEmailSent($this->userNotificationChannel)
        );
    }

    private function log(): void
    {
        UserNotificationSent::create([
            'user_notification_channel_id' => $this->userNotificationChannel->id,
            'sent_at' => Carbon::now(),
            'subject_line' => trans('email.notification_test_email'),
        ]);
    }
}
