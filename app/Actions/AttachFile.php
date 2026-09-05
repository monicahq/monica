<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Say that one more thing points at a file.
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
