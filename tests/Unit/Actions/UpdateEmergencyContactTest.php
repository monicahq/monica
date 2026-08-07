<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEmergencyContact;
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

class UpdateEmergencyContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_emergency_contact_of_the_employee_signed_in(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $result = new UpdateEmergencyContact(
            user: $user,
            name: 'Mose Schrute',
            phone: '+1 570 555 0182',
            relationship: 'Cousin',
        )->execute();

        $this->assertInstanceOf(Employee::class, $result);

        $employee->refresh();

        $this->assertEquals('Mose Schrute', $employee->emergency_contact_name);
        $this->assertEquals('+1 570 555 0182', $employee->emergency_contact_phone);
        $this->assertEquals('Cousin', $employee->emergency_contact_relationship);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmergencyContactUpdate
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

        new UpdateEmergencyContact(
            user: $user,
            name: 'Bob Vance',
        )->execute();

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $employee->refresh()->last_saved_at?->timestamp,
            2,
        );
    }

    #[Test]
    public function it_clears_the_contact_when_nothing_is_given(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->withPrivateInformation()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        new UpdateEmergencyContact(user: $user)->execute();

        $employee->refresh();

        $this->assertNull($employee->emergency_contact_name);
        $this->assertNull($employee->emergency_contact_phone);
        $this->assertNull($employee->emergency_contact_relationship);
    }

    #[Test]
    public function it_throws_when_the_user_has_no_employee_record(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create(['employee_id' => null]);

        new UpdateEmergencyContact(
            user: $user,
            name: 'Toby Flenderson',
        )->execute();
    }
}
