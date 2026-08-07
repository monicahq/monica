<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Logs;

use App\Enums\UserActionEnum;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\Employee;
use App\Models\Log;
use App\Models\User;
use App\ViewModels\Settings\Account\Logs\LogsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_logs_of_the_user_newest_first(): void
    {
        $user = User::factory()->create();

        Log::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserLogin->value,
            'created_at' => now()->subDays(3),
        ]);
        Log::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserPasswordUpdate->value,
            'created_at' => now()->subDay(),
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);
        $logs = $viewModel->logs();

        $this->assertCount(2, $logs);
        $this->assertEquals(UserActionEnum::UserPasswordUpdate->value, $logs[0]->action);
        $this->assertEquals(UserActionEnum::UserLogin->value, $logs[1]->action);
    }

    #[Test]
    public function it_leaves_out_the_logs_of_somebody_else(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $colleague = User::factory()->create(['company_id' => $company->id]);

        Log::factory()->create([
            'company_id' => $company->id,
            'user_id' => $colleague->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertCount(0, $viewModel->logs());
    }

    #[Test]
    public function it_gives_five_logs_a_page(): void
    {
        $user = User::factory()->create();

        Log::factory()->count(7)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertCount(5, $viewModel->logs());
        $this->assertTrue($viewModel->logs()->hasMorePages());
    }

    #[Test]
    public function it_gives_the_last_five_emails_sent_newest_first(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(6)->sequence(fn ($sequence): array => [
            'subject' => 'Email '.$sequence->index,
            'sent_at' => now()->subDays(6 - $sequence->index),
        ])->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertCount(5, $viewModel->emailsSent());
        $this->assertEquals('Email 5', $viewModel->emailsSent()->first()->subject);
        $this->assertTrue($viewModel->hasMoreEmailsSent());
    }

    #[Test]
    public function it_says_there_is_nothing_more_to_read_when_there_are_five_emails_or_fewer(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->count(5)->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertFalse($viewModel->hasMoreEmailsSent());
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

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertCount(0, $viewModel->emailsSent());
        $this->assertFalse($viewModel->hasMoreEmailsSent());
    }

    #[Test]
    public function it_gives_the_name_and_the_company_of_the_signed_in_person(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'display_name' => 'Big Tuna',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: $employee);

        $this->assertEquals('Big Tuna', $viewModel->name());
        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_employee_record(): void
    {
        $user = User::factory()->create([
            'employee_id' => null,
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new LogsViewModel(user: $user, employee: null);

        $this->assertEquals('accountant@vancerefrigeration.com', $viewModel->name());
    }
}
