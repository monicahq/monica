<?php

namespace App\Domains\Settings\ManageNotificationChannels\Services;

use App\Interfaces\ServiceInterface;
use App\Mail\TestEmailSent;
use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends BaseService implements ServiceInterface
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
     * Send a test email.
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

        $this->userNotificationChannel = $this->author->notificationChannels()
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
            'subject_line' => trans('Test email for Monica'),
        ]);
    }
}
