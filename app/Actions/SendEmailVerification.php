<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\VerifyEmail;

/**
 * Send the email that asks somebody to confirm the address they signed up with.
 * The link is signed and expires. `User::sendEmailVerificationNotification()`
 * lands here, so the framework asks for the email the same way the notice screen
 * does when somebody asks for it again.
 */
class SendEmailVerification
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->send();

        return $this->user;
    }

    private function send(): void
    {
        $this->user->notify(new VerifyEmail);
    }
}
