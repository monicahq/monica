<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Say that one more thing points at a file.
 *
 * Whatever attaches it, a value on a record or an activity, calls this rather
 * than writing the count itself, so the number the quota and the sweep read is
 * maintained in one place.
 *
 * A file something points at is not an orphan, so the moment it went orphan is
 * forgotten. Detaching it later starts the grace period again from that later
 * moment, which is what makes the period mean what it says.
 */
class AttachFile
{
    public function __construct(
        private readonly User $user,
        private readonly File $file,
    ) {}

    public function execute(): File
    {
        $this->validate();
        $this->attach();

        return $this->file;
    }

    private function validate(): void
    {
        if ($this->file->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This file is in another account.');
        }
    }

    private function attach(): void
    {
        $this->file->increment('reference_count');

        $this->file->orphaned_at = null;
        $this->file->save();
    }
}
