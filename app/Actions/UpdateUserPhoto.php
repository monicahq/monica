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
 * Set the photo a user shows. Two square versions are written, one at the size
 * the app displays and one at twice it, so a dense screen has a sharp one to
 * pick from. Somebody may only do this to their own profile, so the action
 * edits whoever is signed in.
 *
 * A user only ever has one photo, so an earlier one is removed once the new one
 * is in place.
 */
class UpdateUserPhoto
{
    /**
     * The mime types we accept. Anything else is rejected, whatever the
     * extension of the uploaded file claims.
     */
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

    /**
     * The file is checked here as well as in the controller, on what it
     * actually is rather than on what its name claims.
     */
    private function validate(): void
    {
        if (! in_array($this->file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('The file must be a jpeg, png or webp image');
        }

        if ($this->file->getSize() > self::MAX_SIZE_IN_BYTES) {
            throw new InvalidArgumentException('The file must not be larger than 5 MB');
        }
    }

    /**
     * The name the user gave the file never reaches the disk: we generate a
     * random one instead, and the two versions are named after it.
     */
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

    /**
     * The files of the earlier photo are only removed once the new one is
     * saved, so a failure halfway through leaves the user with a working photo
     * rather than none.
     */
    private function removePrevious(): void
    {
        if ($this->previousPath === null) {
            return;
        }

        foreach (User::photoPixelSizes() as $pixels) {
            $this->disk()->delete($this->previousPath.'_'.$pixels.'.webp');
        }
    }

    /**
     * The disk lives here alone so it can be swapped in one place.
     */
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
        )->onQueue('low');
    }
}
