<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteAFileInUse;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take a file off the disk and give its bytes back to the account.
 */
class DestroyFile
{
    private string $name;

    public function __construct(
        private readonly Account $account,
        private readonly File $file,
        private readonly ?User $user = null,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->file->account_id !== $this->account->id) {
            throw new ModelNotFoundException('This file is in another account.');
        }

        if ($this->user !== null && $this->user->account_id !== $this->account->id) {
            throw new ModelNotFoundException('This user is in another account.');
        }

        if (! $this->file->isOrphan()) {
            throw new CannotDeleteAFileInUse("This file is still pointed at by {$this->file->reference_count} things.");
        }
    }

    private function destroy(): void
    {
        $this->name = $this->file->original_name;
        $size = $this->file->size_in_bytes;

        DB::transaction(function () use ($size): void {
            // The purge loads files before destroying them, so something may have attached this one since.
            $deleted = File::query()
                ->whereKey($this->file->id)
                ->where('reference_count', 0)
                ->delete();

            if ($deleted !== 1) {
                throw new CannotDeleteAFileInUse('This file is pointed at again.');
            }

            Account::query()
                ->whereKey($this->account->id)
                ->where('storage_used_in_bytes', '>=', $size)
                ->decrement('storage_used_in_bytes', $size);
        });

        $this->file->disk()->delete($this->file->path);

        foreach ($this->file->variants ?? [] as $variant) {
            $this->file->disk()->delete($this->file->path.$variant['suffix']);
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->user,
            action: UserActionEnum::FileDeletion,
            vault: $this->file->vault,
            parameters: ['name' => $this->name],
            loggable: $this->file,
        )->onQueue('low');
    }
}
