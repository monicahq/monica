<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Logs;

use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Employee;
use App\Models\User;
use App\ViewModels\Settings\Account\Logs\EmailsSentViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailsSentViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_emails_sent_newest_first(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'subject' => 'Confirm your email address',
            'sent_at' => now()->subDays(3),
        ]);
        EmailSent::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'subject' => 'A sign-in from a new place',
            'sent_at' => now()->subDay(),
        ]);

        $viewModel = new EmailsSentViewModel(user: $user, employee: null);

        $this->assertCount(2, $viewModel->emailsSent());
        $this->assertEquals('A sign-in from a new place', $viewModel->emailsSent()->first()->subject);
    }

    #[Test]
    public function it_hides_the_emails_sent_to_somebody_else(): void
    {
        $user = User::factory()->create();
        $colleague = User::factory()->create(['company_id' => $user->company_id]);
        EmailSent::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $colleague->id,
        ]);

        $viewModel = new EmailsSentViewModel(user: $user, employee: null);

        $this->assertCount(0, $viewModel->emailsSent());
    }

    #[Test]
    public function it_gives_ten_emails_a_page(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(11)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new EmailsSentViewModel(user: $user, employee: null);

        $this->assertCount(10, $viewModel->emailsSent());
        $this->assertTrue($viewModel->emailsSent()->hasMorePages());
    }

    #[Test]
    public function it_gives_the_name_and_the_company_of_the_person_signed_in(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Kevin',
            'last_name' => 'Malone',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new EmailsSentViewModel(user: $user, employee: $employee);

        $this->assertEquals('Kevin Malone', $viewModel->name());
        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_employee_record(): void
    {
        $user = User::factory()->create([
            'employee_id' => null,
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new EmailsSentViewModel(user: $user, employee: null);

        $this->assertEquals('accountant@vancerefrigeration.com', $viewModel->name());
        $this->assertNull($viewModel->employee());
    }

    #[Test]
    public function it_gives_the_employee_record_the_avatar_draws_from(): void
    {
        $company = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new EmailsSentViewModel(user: $user, employee: $employee);

        $this->assertTrue($employee->is($viewModel->employee()));
    }
}
