<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use App\Models\Vault;
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
    public function it_still_writes_the_entry_when_what_it_is_about_has_gone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $job = new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::VaultDeletion,
            loggable: $vault,
        );

        $type = $vault->getMorphClass();
        $id = $vault->id;

        $vault->forceDelete();

        unserialize(serialize($job))->handle();

        $log = Log::query()->latest()->first();

        $this->assertSame($type, $log->loggable_type);
        $this->assertEquals($id, $log->loggable_id);
        $this->assertNull($log->loggable);
    }

    #[Test]
    public function it_says_whether_it_is_about_a_given_row(): void
    {
        $user = User::factory()->create();
        $somebody_else = User::factory()->create();

        $job = new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
            loggable: $user,
        );

        $this->assertTrue($job->isAbout($user));
        $this->assertFalse($job->isAbout($somebody_else));
    }

    #[Test]
    public function it_logs_the_vault_the_action_touched(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::VaultCreation,
            vault: $vault,
        )->handle();

        $this->assertEquals($vault->id, Log::query()->latest()->first()->vault_id);
    }

    #[Test]
    public function it_touches_no_vault_unless_it_is_given_one(): void
    {
        $user = User::factory()->create();

        new LogUserAction(
            account: $user->account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
        )->handle();

        $this->assertNull(Log::query()->latest()->first()->vault_id);
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

    #[Test]
    public function it_reads_the_interface_it_came_through_from_the_request(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        request()->attributes->set('log_source', LogSourceEnum::Dav);
        request()->attributes->set('log_source_detail', 'Dwight’s phone');

        new LogUserAction(
            account: $account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
        )->handle();

        $this->assertDatabaseHas('logs', [
            'account_id' => $account->id,
            'source' => LogSourceEnum::Dav->value,
            'source_detail' => 'Dwight’s phone',
        ]);
    }

    #[Test]
    public function it_lets_the_caller_name_the_interface_instead(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        request()->attributes->set('log_source', LogSourceEnum::Dav);

        new LogUserAction(
            account: $account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
            source: LogSourceEnum::Api,
        )->handle();

        $this->assertDatabaseHas('logs', [
            'account_id' => $account->id,
            'source' => LogSourceEnum::Api->value,
        ]);
    }

    #[Test]
    public function it_reads_as_the_browser_when_nothing_says_otherwise(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        new LogUserAction(
            account: $account,
            user: $user,
            action: UserActionEnum::UserProfileUpdate,
        )->handle();

        $this->assertDatabaseHas('logs', [
            'account_id' => $account->id,
            'source' => LogSourceEnum::Web->value,
            'source_detail' => null,
        ]);
    }
}
