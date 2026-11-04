<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

/**
 * Record that somebody hid the banner offering full access, so it never shows again.
 * The first moment is kept, and nothing ever clears it.
 */
class MarkFullAccessOfferAsDismissed
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        if ($this->user->full_access_offer_dismissed_at !== null) {
            return $this->user;
        }

        $this->user->full_access_offer_dismissed_at = now();
        $this->user->save();

        return $this->user;
    }
}
