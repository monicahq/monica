<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Remove the photo of an employee, falling them back to their initials.
 * Somebody may only do this to their own record, so the action takes the user
 * and edits whoever is signed in.
 */
class DestroyEmployeePhoto
{
    private Employee $employee;

    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): Employee
    {
        $this->validate();

        $path = $this->employee->photo_path;

        if ($path === null) {
            return $this->employee;
        }

        $this->employee->photo_path = null;
        $this->employee->last_saved_at = now();
        $this->employee->save();

        // The files go last: deleting one cannot be undone, and a record that
        // points at nothing is a smaller problem than a file nothing points at.
        foreach (Employee::photoPixelSizes() as $pixels) {
            $this->disk()->delete($path.'_'.$pixels.'.webp');
        }

        $this->log();

        return $this->employee;
    }

    /**
     * An account does not always belong to somebody who works for the company,
     * and one that does not has no record to edit.
     */
    private function validate(): void
    {
        $employee = $this->user->employee;

        if ($employee === null) {
            throw new ModelNotFoundException('Employee not found');
        }

        $this->employee = $employee;
    }

    /**
     * The disk lives here alone so it can be swapped in one place.
     */
    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::EmployeePhotoDeletion,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
