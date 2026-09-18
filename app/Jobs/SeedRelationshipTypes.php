<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultRelationshipTypes;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Give a new account the vocabulary it describes relationships with. It waits on the queue because
 * nobody reads that vocabulary while the sign-up page is still open.
 */
class SeedRelationshipTypes implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateDefaultRelationshipTypes(user: $this->user)->execute();
    }
}
