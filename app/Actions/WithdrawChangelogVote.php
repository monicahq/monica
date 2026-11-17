<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Models\ChangelogVote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take back a vote on a change of What's new.
 */
class WithdrawChangelogVote
{
    public function __construct(
        private readonly User $user,
        private readonly string $entryId,
    ) {}

    public function execute(): void
    {
        $this->validate();

        ChangelogVote::query()
            ->where('user_id', $this->user->id)
            ->where('entry_id', $this->entryId)
            ->delete();
    }

    private function validate(): void
    {
        if (! app(ChangelogEntries::class)->find($this->entryId) instanceof ChangelogEntry) {
            throw new ModelNotFoundException('This change does not exist.');
        }
    }
}
