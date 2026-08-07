<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Security;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\ViewModels\Settings\Account\Security\SecurityViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityViewModelTest extends TestCase
{
    use RefreshDatabase;

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

        $viewModel = new SecurityViewModel(user: $user, employee: $employee);

        $this->assertEquals('Big Tuna', $viewModel->name());
        $this->assertEquals('Dunder Mifflin', $viewModel->companyName());
        $this->assertTrue($viewModel->employee()->is($employee));
    }

    #[Test]
    public function it_falls_back_to_the_email_when_there_is_no_employee_record(): void
    {
        $user = User::factory()->create([
            'employee_id' => null,
            'email' => 'accountant@vancerefrigeration.com',
        ]);

        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertEquals('accountant@vancerefrigeration.com', $viewModel->name());
        $this->assertNull($viewModel->employee());
    }

    #[Test]
    public function it_gives_how_long_ago_the_password_was_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()->subDays(2)]);

        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertEquals('2 days ago', $viewModel->passwordChangedAt());
    }

    #[Test]
    public function it_gives_nothing_when_the_password_was_never_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => null]);

        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertNull($viewModel->passwordChangedAt());
    }

    #[Test]
    public function it_says_whether_the_account_signs_in_through_a_provider(): void
    {
        $user = User::factory()->create();
        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertFalse($viewModel->usesSingleSignOn());

        $singleSignOnUser = User::factory()->singleSignOn()->create();
        $singleSignOnViewModel = new SecurityViewModel(user: $singleSignOnUser, employee: null);

        $this->assertTrue($singleSignOnViewModel->usesSingleSignOn());
    }

    #[Test]
    public function it_gives_an_empty_list_when_no_api_key_has_been_made(): void
    {
        $user = User::factory()->create();

        $viewModel = new SecurityViewModel(user: $user, employee: null);

        $this->assertSame([], $viewModel->apiKeys());
        $this->assertEquals('Active · no API keys', $viewModel->apiKeysHeader());
    }

    #[Test]
    public function it_gives_the_api_keys_newest_first(): void
    {
        $user = User::factory()->create();

        $older = $user->createToken('Beet farm sync')->accessToken;
        $older->forceFill(['created_at' => now()->subDays(3)])->save();

        $user->createToken('Dundie awards bot');

        $apiKeys = new SecurityViewModel(user: $user, employee: null)->apiKeys();

        $this->assertCount(2, $apiKeys);
        $this->assertEquals('Dundie awards bot', $apiKeys[0]['name']);
        $this->assertEquals('Beet farm sync', $apiKeys[1]['name']);
        $this->assertEquals('3 days ago', $apiKeys[1]['createdAt']);
    }

    #[Test]
    public function it_gives_nothing_for_the_last_use_until_something_uses_the_key(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');

        $apiKeys = new SecurityViewModel(user: $user, employee: null)->apiKeys();

        $this->assertNull($apiKeys[0]['lastUsedAt']);
    }

    #[Test]
    public function it_gives_how_long_ago_the_api_key_was_last_used(): void
    {
        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;
        $apiKey->forceFill(['last_used_at' => now()->subHours(5)])->save();

        $apiKeys = new SecurityViewModel(user: $user, employee: null)->apiKeys();

        $this->assertEquals('5 hours ago', $apiKeys[0]['lastUsedAt']);
    }

    #[Test]
    public function it_counts_the_api_keys_in_the_header(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');

        $this->assertEquals('Active · 1 API key', new SecurityViewModel(user: $user, employee: null)->apiKeysHeader());

        $user->createToken('Beet farm sync');

        $this->assertEquals('Active · 2 API keys', new SecurityViewModel(user: $user, employee: null)->apiKeysHeader());
    }
}
