<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RegenerateTwoFactorRecoveryCodes;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegenerateTwoFactorRecoveryCodesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_out_a_new_set_and_throws_the_old_one_away(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

        new RegenerateTwoFactorRecoveryCodes(user: $user)->execute();

        $codes = $user->refresh()->two_factor_recovery_codes;

        $this->assertCount(8, $codes);
        $this->assertNotContains('scranton-1', $codes);
        $this->assertNotContains('scranton-2', $codes);
    }

    #[Test]
    public function it_logs_the_change(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->twoFactor()->create(['company_id' => $company->id]);

        new RegenerateTwoFactorRecoveryCodes(user: $user)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TwoFactorRecoveryCodesRegenerated
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_throws_when_two_factor_authentication_is_not_in_use(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create(['two_factor_confirmed_at' => null]);

        new RegenerateTwoFactorRecoveryCodes(user: $user)->execute();
    }
}
