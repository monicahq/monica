<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogAdministratorActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_down_what_the_administrator_did(): void
    {
        $administrator = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $account = Account::factory()->create();

        new LogAdministratorAction(
            administrator: $administrator,
            action: AdministratorActionEnum::LifetimeAccessGranted,
            account: $account,
            parameters: ['name' => 'Dwight Schrute'],
        )->handle();

        $this->assertDatabaseHas('administrator_logs', [
            'user_id' => $administrator->id,
            'user_email' => 'michael.scott@dundermifflin.com',
            'action' => 'lifetime_access_granted',
            'account_id' => $account->id,
        ]);
    }

    #[Test]
    public function it_records_the_console_when_nobody_was_signed_in(): void
    {
        new LogAdministratorAction(
            administrator: null,
            action: AdministratorActionEnum::AdministratorAccessGranted,
        )->handle();

        $this->assertDatabaseHas('administrator_logs', [
            'user_id' => null,
            'user_email' => Log::SYSTEM,
        ]);
    }

    #[Test]
    public function it_keeps_what_the_action_was_about_even_once_that_is_gone(): void
    {
        $administrator = User::factory()->create();
        $subject = User::factory()->create();

        $job = new LogAdministratorAction(
            administrator: $administrator,
            action: AdministratorActionEnum::UserDeactivated,
            subject: $subject,
        );

        $this->assertTrue($job->isAbout($subject));

        $subject->forceDelete();
        $job->handle();

        $this->assertDatabaseHas('administrator_logs', [
            'subject_type' => (new User)->getMorphClass(),
            'subject_id' => $subject->id,
        ]);
    }
}
