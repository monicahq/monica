<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Say that one fewer thing points at a file.
 *
 * Detaching never deletes. The moment the last thing lets go is written down
 * instead, and the sweep comes for it once the grace period has run out. That
 * gap is the whole point: restoring a record to how it looked an hour ago has
 * to find the file it pointed at still on the disk.
 *
 * The count never goes below nought, since a count of minus one would keep a
 * file alive that nothing has pointed at for a year.
 */
class DetachFile
{
    public function __construct(
        private readonly User $user,
        private readonly File $file,
    ) {}

    public function execute(): File
    {
        $this->validate();
        $this->detach();

        return $this->file;
    }

    private function validate(): void
    {
        if ($this->file->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This file is in another account.');
        }
    }

    private function detach(): void
    {
        if ($this->file->reference_count > 0) {
            $this->file->decrement('reference_count');
        }

        $this->file->refresh();

        if (! $this->file->isOrphan()) {
            return;
        }

        $this->file->orphaned_at = now();
        $this->file->save();
    }
}
