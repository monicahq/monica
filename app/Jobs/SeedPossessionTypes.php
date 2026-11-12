<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultPossessionTypes;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Give an account the possession types it starts with. It waits on the queue because twenty five
 * types and their fields are a few hundred rows nobody is looking at while the sign-up page is still
 * open.
 */
class SeedPossessionTypes implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateDefaultPossessionTypes(user: $this->user)->execute();
    }
}
