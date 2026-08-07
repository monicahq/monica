<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Set the photo an employee shows their colleagues. Two square versions are
 * written, one at the size the app displays and one at twice it, so a dense
 * screen has a sharp one to pick from. Somebody may only do this to their own
 * record, which is why the action takes the user rather than the employee.
 *
 * An employee only ever has one photo, so an earlier one is removed once the
 * new one is in place.
 */
class UpdateEmployeePhoto
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

    private Employee $employee;

    private ?string $previousPath = null;

    private string $path;

    public function __construct(
        private readonly User $user,
        private readonly UploadedFile $file,
    ) {}

    public function execute(): Employee
    {
        $this->validate();
        $this->store();
        $this->save();
        $this->removePrevious();
        $this->log();

        return $this->employee;
    }

    /**
     * An account does not always belong to somebody who works for the company,
     * and one that does not has no record to put a photo on. The file is
     * checked here as well as in the controller, on what it actually is rather
     * than on what its name claims.
     */
    private function validate(): void
    {
        $employee = $this->user->employee;

        if ($employee === null) {
            throw new ModelNotFoundException('Employee not found');
        }

        $this->employee = $employee;

        if (! in_array($this->file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('The file must be a jpeg, png or webp image');
        }

        if ($this->file->getSize() > self::MAX_SIZE_IN_BYTES) {
            throw new InvalidArgumentException('The file must not be larger than 5 MB');
        }
    }

    /**
     * The name the employee gave the file never reaches the disk: we generate a
     * random one instead, and the two versions are named after it.
     */
    private function store(): void
    {
        $this->previousPath = $this->employee->photo_path;

        $directory = 'photos/'.$this->employee->id;
        $stem = Str::uuid()->toString();

        foreach (Employee::photoPixelSizes() as $pixels) {
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
        $this->employee->photo_path = $this->path;
        $this->employee->last_saved_at = now();
        $this->employee->save();
    }

    /**
     * The files of the earlier photo are only removed once the new one is
     * saved, so a failure halfway through leaves the employee with a working
     * photo rather than none.
     */
    private function removePrevious(): void
    {
        if ($this->previousPath === null) {
            return;
        }

        foreach (Employee::photoPixelSizes() as $pixels) {
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
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::EmployeePhotoUpdate,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
