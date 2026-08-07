<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisableTwoFactorAuthentication;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisableTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_forgets_the_secret_and_the_recovery_codes(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

        $result = new DisableTwoFactorAuthentication(user: $user)->execute();

        $this->assertInstanceOf(User::class, $result);

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertFalse($user->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_logs_the_change(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

        new DisableTwoFactorAuthentication(user: $user)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorDisabled
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }
}
