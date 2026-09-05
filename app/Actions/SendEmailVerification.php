<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\VerifyEmail;

/**
 * Send the email that asks somebody to confirm the address they signed up with.
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
