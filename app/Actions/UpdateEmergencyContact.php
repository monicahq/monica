<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Update who to call about an employee when something happens to them. Somebody
 * may only do this to their own record, so the action takes the user and edits
 * whoever is signed in.
 *
 * The details themselves are not logged, only that they changed. Who somebody
 * lives with is not something to leave lying in a log.
 */
class UpdateEmergencyContact
{
    private Employee $employee;

    public function __construct(
        private readonly User $user,
        private ?string $name = null,
        private ?string $phone = null,
        private ?string $relationship = null,
    ) {}

    public function execute(): Employee
    {
        $this->validate();
        $this->sanitize();
        $this->update();
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

    private function sanitize(): void
    {
        $this->name = TextSanitizer::nullablePlainText($this->name);
        $this->phone = TextSanitizer::nullablePlainText($this->phone);
        $this->relationship = TextSanitizer::nullablePlainText($this->relationship);
    }

    private function update(): void
    {
        $this->employee->emergency_contact_name = $this->name;
        $this->employee->emergency_contact_phone = $this->phone;
        $this->employee->emergency_contact_relationship = $this->relationship;
        $this->employee->last_saved_at = now();
        $this->employee->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::EmergencyContactUpdate,
        )->onQueue('low');
    }
}
