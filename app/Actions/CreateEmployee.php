<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Create an employee of a company. Only a member of the company may do so. The
 * information the employee keeps private is not set here, as they fill it in
 * themselves once they have an account.
 */
class CreateEmployee
{
    private Employee $employee;

    public function __construct(
        private readonly User $user,
        private readonly Company $company,
        private string $firstName,
        private string $lastName,
        private ?string $employeeNumber = null,
        private ?string $displayName = null,
        private ?string $workEmail = null,
        private ?string $customTitle = null,
        private ?string $country = null,
        private ?string $timezone = null,
        private readonly ?Carbon $hiredAt = null,
    ) {}

    public function execute(): Employee
    {
        $this->validate();
        $this->sanitize();
        $this->create();
        $this->log();

        return $this->employee;
    }

    private function validate(): void
    {
        if ($this->user->company_id !== $this->company->id) {
            throw new ModelNotFoundException('Company not found');
        }
    }

    private function sanitize(): void
    {
        $this->firstName = TextSanitizer::plainText($this->firstName);
        $this->lastName = TextSanitizer::plainText($this->lastName);
        $this->employeeNumber = TextSanitizer::nullablePlainText($this->employeeNumber);
        $this->displayName = TextSanitizer::nullablePlainText($this->displayName);
        $this->customTitle = TextSanitizer::nullablePlainText($this->customTitle);
        $this->timezone = TextSanitizer::nullablePlainText($this->timezone);

        $workEmail = TextSanitizer::nullablePlainText($this->workEmail);
        $this->workEmail = $workEmail === null ? null : mb_strtolower($workEmail);

        $country = TextSanitizer::nullablePlainText($this->country);
        $this->country = $country === null ? null : mb_strtoupper($country);
    }

    private function create(): void
    {
        $this->employee = Employee::query()->create([
            'company_id' => $this->company->id,
            'employee_number' => $this->employeeNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
            'work_email' => $this->workEmail,
            'custom_title' => $this->customTitle,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'hired_at' => $this->hiredAt,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->user,
            action: UserActionEnum::EmployeeCreation,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
