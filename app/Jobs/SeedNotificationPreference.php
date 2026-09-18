<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateNotificationPreference;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Give a new account the row that says how it wants to be told things. It goes first in the chain,
 * being the only part of the seeding whose absence a reader meets as an error rather than an empty
 * screen: the preferences endpoint asks for the row with firstOrFail.
 */
class SeedNotificationPreference implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateNotificationPreference(account: $this->user->account)->execute();
    }
}
