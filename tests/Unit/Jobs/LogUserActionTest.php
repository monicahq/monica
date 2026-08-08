<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogUserActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_the_action_of_the_user(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new LogUserAction(
            account: $account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
            parameters: ['name' => 'Michael Scott'],
        )->handle();

        $this->assertDatabaseHas('logs', [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'user_email' => 'michael.scott@dundermifflin.com',
            'action' => UserActionEnum::UserProfileUpdate->value,
        ]);

        $this->assertEquals(['name' => 'Michael Scott'], Log::query()->latest()->first()->parameters);
    }

    #[Test]
    public function it_keeps_the_email_of_the_user_once_they_are_deleted(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        new LogUserAction(
            account: $account,
            user: $user,
            action: UserActionEnum::UserPasswordUpdate,
        )->handle();

        $user->forceDelete();

        $log = Log::query()->latest()->first();

        $this->assertNull($log->user_id);
        $this->assertEquals('michael.scott@dundermifflin.com', $log->user_email);
    }

    #[Test]
    public function it_logs_what_the_action_was_performed_on_and_what_it_changed(): void
    {
        $user = User::factory()->create();

        new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
            loggable: $user,
            changes: ['last_name' => ['before' => 'Levinson', 'after' => 'Levinson-Gould']],
        )->handle();

        $log = Log::query()->latest()->first();

        $this->assertTrue($user->is($log->loggable));
        $this->assertEquals(['last_name' => ['before' => 'Levinson', 'after' => 'Levinson-Gould']], $log->changes);
    }

    #[Test]
    public function it_logs_the_interface_the_action_came_from(): void
    {
        $user = User::factory()->create();

        new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::ApiKeyCreation,
            source: LogSourceEnum::Api,
            sourceDetail: 'Dundie awards bot',
            ipAddress: '10.0.0.1',
        )->handle();

        $log = Log::query()->latest()->first();

        $this->assertEquals(LogSourceEnum::Api, $log->source);
        $this->assertEquals('Dundie awards bot', $log->source_detail);
        $this->assertEquals('10.0.0.1', $log->ip_address);
    }

    #[Test]
    public function it_takes_the_action_for_a_web_one_unless_told_otherwise(): void
    {
        $user = User::factory()->create();

        new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::UserLogin,
        )->handle();

        $this->assertEquals(LogSourceEnum::Web, Log::query()->latest()->first()->source);
    }

    #[Test]
    public function it_keeps_the_address_the_request_came_from(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.profile.index'));

        new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::UserLogin,
        )->handle();

        $this->assertEquals('127.0.0.1', Log::query()->latest()->first()->ip_address);
    }

    #[Test]
    public function it_logs_an_action_nobody_was_behind(): void
    {
        $account = Account::factory()->create();

        new LogUserAction(
            account: $account,
            user: null,
            action: UserActionEnum::UserLogin,
            source: LogSourceEnum::System,
        )->handle();

        $log = Log::query()->latest()->first();

        $this->assertNull($log->user_id);
        $this->assertEquals(Log::SYSTEM, $log->user_email);
        $this->assertTrue($log->wasPerformedBySystem());
    }
}
