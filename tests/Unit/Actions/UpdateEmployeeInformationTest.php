<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEmployeeInformation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEmployeeInformationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_information_of_the_employee_signed_in(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $result = new UpdateEmployeeInformation(
            user: $user,
            firstName: 'Dwight',
            lastName: 'Schrute',
            displayName: 'Assistant to the Regional Manager',
            workEmail: 'Dwight.Schrute@DunderMifflin.com',
        )->execute();

        $this->assertInstanceOf(Employee::class, $result);

        $employee->refresh();

        $this->assertEquals('Dwight', $employee->first_name);
        $this->assertEquals('Schrute', $employee->last_name);
        $this->assertEquals('Assistant to the Regional Manager', $employee->display_name);
        $this->assertEquals('dwight.schrute@dundermifflin.com', $employee->work_email);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmployeeInformationUpdate
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_stamps_when_the_record_was_last_saved(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'last_saved_at' => null,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        new UpdateEmployeeInformation(
            user: $user,
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $employee->refresh()->last_saved_at?->timestamp,
            2,
        );
    }

    #[Test]
    public function it_clears_the_optional_fields_when_they_are_not_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'display_name' => 'Big Tuna',
            'work_email' => 'jim.halpert@dundermifflin.com',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        new UpdateEmployeeInformation(
            user: $user,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();

        $employee->refresh();

        $this->assertNull($employee->display_name);
        $this->assertNull($employee->work_email);
    }

    #[Test]
    public function it_throws_when_the_user_has_no_employee_record(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create(['employee_id' => null]);

        new UpdateEmployeeInformation(
            user: $user,
            firstName: 'Creed',
            lastName: 'Bratton',
        )->execute();
    }
}
