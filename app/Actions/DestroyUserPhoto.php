<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Remove the photo of a user, falling them back to their initials.
 */
class DestroyUserPhoto
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $path = $this->user->photo_path;

        if ($path === null) {
            return $this->user;
        }

        $this->user->photo_path = null;
        $this->user->last_saved_at = now();
        $this->user->save();

        foreach (User::photoPixelSizes() as $pixels) {
            $this->disk()->delete($path.'_'.$pixels.'.webp');
        }

        $this->log();

        return $this->user;
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserPhotoDeletion,
            parameters: ['name' => $this->user->name],
        )->onQueue('low');
    }
}
