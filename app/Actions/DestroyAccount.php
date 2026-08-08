<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Delete an account and everything under it. Only the person the account
 * belongs to may do so.
 *
 * This is the one hard delete in the product. The rows go through the foreign
 * keys, which cascade from the account down. What the database cannot reach
 * goes first: the files on disk, and the API keys, which point at the user
 * through a morph rather than a constraint.
 *
 * Nothing is logged. The log rows of the account are about to be deleted with
 * it, and a job writing one afterwards would point at an account that is gone.
 */
class DestroyAccount
{
    public function __construct(
        private readonly User $user,
        private readonly Account $account,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroyPhoto();
        $this->destroyApiKeys();
        $this->destroy();
    }

    private function validate(): void
    {
        if ($this->user->account_id !== $this->account->id) {
            throw new ModelNotFoundException('Account not found');
        }
    }

    private function destroyPhoto(): void
    {
        if (! $this->user->hasPhoto()) {
            return;
        }

        foreach (User::photoPixelSizes() as $pixels) {
            $this->disk()->delete($this->user->photoVariantPath($pixels));
        }
    }

    private function destroyApiKeys(): void
    {
        $this->user->tokens()->delete();
    }

    private function destroy(): void
    {
        $this->account->delete();
    }

    /**
     * The disk lives here alone so it can be swapped in one place.
     */
    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }
}
