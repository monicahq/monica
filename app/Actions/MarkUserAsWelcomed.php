<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

/**
 * Record that somebody has read the welcome screen, so the home address stops sending them there.
 * The first moment is kept, since the screen stays open to anybody who asks for it again.
 */
class MarkUserAsWelcomed
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        if ($this->user->welcomed_at !== null) {
            return $this->user;
        }

        $this->user->welcomed_at = now();
        $this->user->save();

        return $this->user;
    }
}
