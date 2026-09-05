<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Say that one fewer thing points at a file.
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
