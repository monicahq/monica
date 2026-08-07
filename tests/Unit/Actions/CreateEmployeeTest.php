<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEmployee;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_employee(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $employee = new CreateEmployee(
            user: $user,
            company: $company,
            firstName: 'Michael',
            lastName: 'Scott',
            employeeNumber: 'EMP-0001',
            displayName: 'The World Best Boss',
            workEmail: 'michael.scott@dundermifflin.com',
            customTitle: 'Regional Manager',
            country: 'us',
            timezone: 'America/New_York',
            hiredAt: Carbon::parse('2005-03-24'),
        )->execute();

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'company_id' => $company->id,
            'employee_number' => 'EMP-0001',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => 'The World Best Boss',
            'work_email' => 'michael.scott@dundermifflin.com',
            'custom_title' => 'Regional Manager',
            'country' => 'US',
            'timezone' => 'America/New_York',
        ]);
        $this->assertEquals('2005-03-24', $employee->hired_at->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmployeeCreation
                && $job->company->id === $company->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'The World Best Boss'],
        );
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $stranger = User::factory()->create();

        new CreateEmployee(
            user: $stranger,
            company: $company,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();
    }
}
