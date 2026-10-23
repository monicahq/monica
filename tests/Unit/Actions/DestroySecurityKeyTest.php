<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroySecurityKey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SecurityKey;
use App\Models\User;
use App\Notifications\SecurityKeyRemoved;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroySecurityKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_key_and_keeps_two_factor_on_while_the_app_remains(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->twoFactor()->create();
        $key = SecurityKey::factory()->for($michael)->create(['name' => 'Dundie key']);

        new DestroySecurityKey(user: $michael, securityKey: $key)->execute();

        $this->assertModelMissing($key);
        $this->assertTrue($michael->refresh()->usesTwoFactorAuthentication());
        $this->assertEquals(['scranton-1', 'scranton-2'], $michael->two_factor_recovery_codes);
        Notification::assertSentTo($michael, SecurityKeyRemoved::class, fn (SecurityKeyRemoved $notification): bool => $notification->name === 'Dundie key');
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SecurityKeyRemoved);
        Queue::assertNotPushed(LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorDisabled);
    }

    #[Test]
    public function it_turns_two_factor_authentication_off_with_the_last_key(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create(['two_factor_recovery_codes' => ['scranton-1']]);
        $key = SecurityKey::factory()->for($michael)->create();

        new DestroySecurityKey(user: $michael, securityKey: $key)->execute();

        $michael->refresh();

        $this->assertFalse($michael->usesTwoFactorAuthentication());
        $this->assertNull($michael->two_factor_recovery_codes);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorDisabled);
    }

    #[Test]
    public function it_refuses_a_key_belonging_to_somebody_else(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroySecurityKey(
            user: User::factory()->create(),
            securityKey: SecurityKey::factory()->create(),
        )->execute();
    }
}
