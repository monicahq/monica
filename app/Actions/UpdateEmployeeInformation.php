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
 * Update the information an employee shows their colleagues. Somebody may only
 * do this to their own record, which is why the action takes the user rather
 * than the employee: it edits whoever is signed in.
 */
class UpdateEmployeeInformation
{
    private Employee $employee;

    public function __construct(
        private readonly User $user,
        private string $firstName,
        private string $lastName,
        private ?string $displayName = null,
        private ?string $workEmail = null,
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
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->displayName = TextSanitizer::nullablePlainText($this->displayName);

        $workEmail = TextSanitizer::nullablePlainText($this->workEmail);
        $this->workEmail = $workEmail === null ? null : mb_strtolower($workEmail);
    }

    private function update(): void
    {
        $this->employee->first_name = $this->firstName;
        $this->employee->last_name = $this->lastName;
        $this->employee->display_name = $this->displayName;
        $this->employee->work_email = $this->workEmail;
        $this->employee->last_saved_at = now();
        $this->employee->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::EmployeeInformationUpdate,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
