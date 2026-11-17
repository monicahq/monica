<?php

declare(strict_types=1);

namespace App\Actions;

use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Models\ChangelogSeenEntry;
use App\Models\User;

/**
 * Mark every visible entry of What's new as seen by a person, so the count in the top bar goes away.
 * Feedback on the product is not the person's own data, so nothing is written to the account's journal.
 */
class MarkChangelogSeen
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): void
    {
        $now = now();

        $rows = array_map(fn (ChangelogEntry $entry): array => [
            'user_id' => $this->user->id,
            'entry_id' => $entry->id,
            'created_at' => $now,
            'updated_at' => $now,
        ], app(ChangelogEntries::class)->unseenBy($this->user));

        if ($rows === []) {
            return;
        }

        ChangelogSeenEntry::query()->insertOrIgnore($rows);
    }
}
