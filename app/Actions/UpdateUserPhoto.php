<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Set the photo a user shows.
 */
class UpdateUserPhoto
{
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const int MAX_SIZE_IN_BYTES = 5 * 1024 * 1024;

    private ?string $previousPath = null;

    private string $path;

    public function __construct(
        private readonly User $user,
        private readonly UploadedFile $file,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->store();
        $this->save();
        $this->removePrevious();
        $this->log();

        return $this->user;
    }

    private function validate(): void
    {
        if (! in_array($this->file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('The file must be a jpeg, png or webp image');
        }

        if ($this->file->getSize() > self::MAX_SIZE_IN_BYTES) {
            throw new InvalidArgumentException('The file must not be larger than 5 MB');
        }
    }

    private function store(): void
    {
        $this->previousPath = $this->user->photo_path;

        $directory = 'photos/'.$this->user->id;
        $stem = Str::uuid()->toString();

        foreach (User::photoPixelSizes() as $pixels) {
            new ResizeImage(
                file: $this->file,
                width: $pixels,
                height: $pixels,
                path: $directory,
                name: $stem.'_'.$pixels.'.webp',
                disk: $this->diskName(),
            )->execute();
        }

        $this->path = $directory.'/'.$stem;
    }

    private function save(): void
    {
        $this->user->photo_path = $this->path;
        $this->user->last_saved_at = now();
        $this->user->save();
    }

    private function removePrevious(): void
    {
        if ($this->previousPath === null) {
            return;
        }

        foreach (User::photoPixelSizes() as $pixels) {
            $this->disk()->delete($this->previousPath.'_'.$pixels.'.webp');
        }
    }

    private function diskName(): string
    {
        return (string) config('filesystems.default');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserPhotoUpdate,
            parameters: ['name' => $this->user->name],
        );
    }
}
