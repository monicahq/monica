<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\User;

/**
 * Update the name a user goes by and the address they sign in with, and ask them to confirm a new address.
 */
class UpdateUserProfile
{
    public function __construct(
        private readonly User $user,
        private string $firstName,
        private string $lastName,
        private string $email,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->update();
        $this->sendVerification();
        $this->log();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->email = mb_strtolower(TextSanitizer::plainText($this->email));
    }

    private function update(): void
    {
        $emailHasChanged = $this->user->email !== $this->email;

        $this->user->first_name = $this->firstName;
        $this->user->last_name = $this->lastName;
        $this->user->email = $this->email;
        $this->user->last_saved_at = now();

        if ($emailHasChanged) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();
    }

    private function sendVerification(): void
    {
        if (! $this->user->wasChanged('email')) {
            return;
        }

        new SendEmailVerification(user: $this->user)->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserProfileUpdate,
            parameters: ['name' => $this->user->name],
        )->onQueue('low');
    }
}
