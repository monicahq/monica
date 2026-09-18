<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultValueLists;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Give a new account the value lists it starts with. It waits on the queue because nine lists and
 * their choices are a few hundred rows nobody is looking at while the sign-up page is still open.
 */
class SeedValueLists implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateDefaultValueLists(user: $this->user)->execute();
    }
}
