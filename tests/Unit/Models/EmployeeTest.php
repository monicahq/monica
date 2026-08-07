<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_company(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($employee->company()->exists());
        $this->assertEquals('Dunder Mifflin', $employee->company->name);
    }

    #[Test]
    public function it_has_one_user(): void
    {
        $employee = Employee::factory()->create();
        User::factory()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertTrue($employee->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $employee->user->email);
    }

    #[Test]
    public function it_returns_the_legal_name_as_the_name_when_the_employee_goes_by_no_other_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'display_name' => null,
        ]);

        $this->assertEquals('Michael Scott', $employee->name);
    }

    #[Test]
    public function it_returns_the_display_name_as_the_name_when_the_employee_goes_by_another_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'display_name' => 'Assistant to the Regional Manager',
        ]);

        $this->assertEquals('Assistant to the Regional Manager', $employee->name);
    }

    #[Test]
    public function it_returns_the_full_name(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'display_name' => 'Pamela',
        ]);

        $this->assertEquals('Pam Beesly', $employee->full_name);
    }

    #[Test]
    public function it_is_employed_when_the_employee_has_no_departure_date(): void
    {
        $employee = Employee::factory()->create(['departed_at' => null]);

        $this->assertTrue($employee->isEmployed());
    }

    #[Test]
    public function it_is_still_employed_when_the_employee_serves_their_notice_period(): void
    {
        $employee = Employee::factory()->create(['departed_at' => Carbon::now()->addMonth()]);

        $this->assertTrue($employee->isEmployed());
    }

    #[Test]
    public function it_is_not_employed_anymore_when_the_employee_already_left(): void
    {
        $employee = Employee::factory()->departed()->create();

        $this->assertFalse($employee->isEmployed());
    }

    #[Test]
    public function it_casts_the_date_the_record_was_last_saved(): void
    {
        $employee = Employee::factory()->create(['last_saved_at' => '2026-07-31 09:30:00']);

        $this->assertInstanceOf(Carbon::class, $employee->last_saved_at);
    }

    #[Test]
    public function it_has_never_been_saved_by_default(): void
    {
        $employee = Employee::factory()->create();

        $this->assertNull($employee->last_saved_at);
    }

    #[Test]
    public function it_has_a_photo_when_a_path_was_stored(): void
    {
        $employee = Employee::factory()->create(['photo_path' => 'photos/1/stanley']);

        $this->assertTrue($employee->hasPhoto());
    }

    #[Test]
    public function it_has_no_photo_by_default(): void
    {
        $employee = Employee::factory()->create();

        $this->assertFalse($employee->hasPhoto());
    }

    #[Test]
    public function it_names_each_version_of_the_photo_after_its_size(): void
    {
        $employee = Employee::factory()->create(['photo_path' => 'photos/1/stanley']);

        $this->assertEquals('photos/1/stanley_96.webp', $employee->photoVariantPath(96));
        $this->assertEquals('photos/1/stanley_192.webp', $employee->photoVariantPath(192));
    }

    #[Test]
    public function it_writes_the_displayed_size_and_twice_it(): void
    {
        $this->assertEquals([96, 192], Employee::photoPixelSizes());
    }
}
