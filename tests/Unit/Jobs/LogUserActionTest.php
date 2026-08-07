<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

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
}
