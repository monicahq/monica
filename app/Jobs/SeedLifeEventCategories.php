<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateDefaultLifeEventCategories;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Give a new account the groups it files its life event vocabulary under. It runs before the natures
 * are seeded, since a nature that finds no group to be filed under is skipped without a word.
 */
class SeedLifeEventCategories implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
    ) {}

    public function handle(): void
    {
        new CreateDefaultLifeEventCategories(user: $this->user)->execute();
    }
}
