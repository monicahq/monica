<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\PasswordConfirmationRequested;

/**
 * Email somebody a link that confirms it is them, for a sensitive change they started while signed in.
 */
class SendPasswordConfirmationLink
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->user->notify(new PasswordConfirmationRequested);

        return $this->user;
    }
}
