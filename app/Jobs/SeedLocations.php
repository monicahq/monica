<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultLocations;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Give an account the locations it starts with. It runs in the chain with the rest of the seeding of
 * a new account, so every default the account receives is written in one place.
 */
class SeedLocations implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateDefaultLocations(user: $this->user)->execute();
    }
}
