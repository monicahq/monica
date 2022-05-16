<?php

namespace App\Settings\ManageNotificationChannels\Services;

use App\Exceptions\EmailAlreadyExistException;
use App\Interfaces\ServiceInterface;
use App\Jobs\CreateAuditLog;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;
use App\Settings\ManageNotificationChannels\Jobs\SendVerificationEmailChannel;
use Illuminate\Support\Str;

class CreateUserNotificationChannel extends BaseService implements ServiceInterface
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
            'label' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'content' => 'required|string|max:65535',
            'verify_email' => 'nullable|boolean',
            'preferred_time' => 'nullable|date_format:H:i',
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
     * Create the new user notification channel for the given user.
     *
     * @param  array  $data
     * @return UserNotificationChannel
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->create();
        $this->verifyChannel();
        $this->log();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $exists = UserNotificationChannel::where('content', $this->data['content'])
            ->exists();

        if ($exists) {
            throw new EmailAlreadyExistException('The email is already taken. Please choose another one.');
        }
    }

    private function create(): void
    {
        $this->userNotificationChannel = UserNotificationChannel::create([
            'user_id' => $this->data['author_id'],
            'label' => $this->data['label'],
            'type' => $this->data['type'],
            'content' => $this->data['content'],
            'preferred_time' => $this->data['preferred_time'],
        ]);

        // add a verification link if the channel is email
        if ($this->data['verify_email']) {
            $uuid = Str::uuid();

            $this->userNotificationChannel->email_verification_link = route('settings.notifications.verification.store', [
                'notification' => $this->userNotificationChannel->id,
                'uuid' => $uuid,
            ]);
            $this->userNotificationChannel->save();
        }
    }

    private function verifyChannel(): void
    {
        if ($this->data['type'] === UserNotificationChannel::TYPE_EMAIL && $this->data['verify_email']) {
            // we need to verify the email address by sending a verification email
            SendVerificationEmailChannel::dispatch($this->userNotificationChannel)->onQueue('high');
        }
    }

    private function log(): void
    {
        CreateAuditLog::dispatch([
            'account_id' => $this->author->account_id,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'action_name' => 'user_notification_channel_created',
            'objects' => json_encode([
                'label' => $this->userNotificationChannel->label,
                'type' => $this->userNotificationChannel->type,
            ]),
        ])->onQueue('low');
    }
}
