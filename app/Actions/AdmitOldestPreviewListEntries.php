<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\AddressAlreadyHoldsAnAccount;
use App\Models\PreviewListEntry;
use App\Models\User;

/**
 * Admit the given number of waiting entries of the preview list, the oldest first, skipping an
 * address that has meanwhile come to hold an account. Answers how many were admitted.
 */
class AdmitOldestPreviewListEntries
{
    public function __construct(
        private readonly User $administrator,
        private readonly int $count,
    ) {}

    public function execute(): int
    {
        $entries = PreviewListEntry::query()
            ->whereNull('admitted_at')
            ->whereNull('registered_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($this->count)
            ->get();

        $admitted = 0;

        foreach ($entries as $entry) {
            try {
                new AdmitPreviewListEntry(
                    administrator: $this->administrator,
                    entry: $entry,
                )->execute();
            } catch (AddressAlreadyHoldsAnAccount) {
                continue;
            }

            $admitted++;
        }

        return $admitted;
    }
}
