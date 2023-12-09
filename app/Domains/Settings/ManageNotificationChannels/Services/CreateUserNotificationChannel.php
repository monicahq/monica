<?php

namespace App\Domains\Settings\ManageNotificationChannels\Services;

use App\Domains\Settings\ManageNotificationChannels\Jobs\SendVerificationEmailChannel;
use App\Interfaces\ServiceInterface;
use App\Models\UserNotificationChannel;
use App\Services\BaseService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUserNotificationChannel extends BaseService implements ServiceInterface
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
            'label' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'content' => 'required|string|max:65535',
            'verify_email' => 'nullable|boolean',
            'preferred_time' => 'nullable',
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
     * Create the new user notification channel for the given user.
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->create();
        $this->verifyChannel();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $exists = UserNotificationChannel::where('content', $this->data['content'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['content' => trans('The email is already taken. Please choose another one.')]);
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

        $this->userNotificationChannel->verification_token = (string) Str::uuid();
        $this->userNotificationChannel->save();
    }

    private function verifyChannel(): void
    {
        if ($this->data['type'] === UserNotificationChannel::TYPE_EMAIL && $this->data['verify_email']) {
            // we need to verify the email address by sending a verification email
            SendVerificationEmailChannel::dispatch($this->userNotificationChannel)->onQueue('high');
        }
    }
}
