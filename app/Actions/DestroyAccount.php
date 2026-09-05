<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Delete an account and everything under it.
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

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }
}
