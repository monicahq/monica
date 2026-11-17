<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ChangelogVoteDirectionEnum;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Models\ChangelogVote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Say whether a change of What's new is liked or not. A person holds one vote per entry, so voting
 * the other way replaces the first vote.
 */
class CastChangelogVote
{
    public function __construct(
        private readonly User $user,
        private readonly string $entryId,
        private readonly ChangelogVoteDirectionEnum $direction,
    ) {}

    public function execute(): ChangelogVote
    {
        $this->validate();

        return ChangelogVote::query()->updateOrCreate(
            ['user_id' => $this->user->id, 'entry_id' => $this->entryId],
            ['direction' => $this->direction],
        );
    }

    private function validate(): void
    {
        if (! app(ChangelogEntries::class)->find($this->entryId) instanceof ChangelogEntry) {
            throw new ModelNotFoundException('This change does not exist.');
        }
    }
}
