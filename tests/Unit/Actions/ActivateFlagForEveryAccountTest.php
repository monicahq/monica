<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateFlagForEveryAccount;
use App\Actions\DeactivateFlag;
use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateFlagForEveryAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_activates_the_flag_for_every_account_and_writes_it_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $pam = User::factory()->create();
        $this->assertFalse($pam->account->features()->active('example-capability'));

        new ActivateFlagForEveryAccount(
            flag: 'example-capability',
            administrator: $michael,
        )->execute();

        Feature::flushCache();

        $this->assertTrue($pam->account->features()->active('example-capability'));
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::FlagActivatedForEveryAccount
            && $job->administrator?->is($michael) === true
            && $job->account === null
            && $job->parameters === ['flag' => 'example-capability']);
    }

    #[Test]
    public function it_covers_an_account_created_afterwards(): void
    {
        Queue::fake();

        new ActivateFlagForEveryAccount(
            flag: 'example-capability',
        )->execute();

        $ryan = User::factory()->create();

        $this->assertTrue($ryan->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_lets_a_later_switch_for_one_account_override_it(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $jim = User::factory()->create();

        new ActivateFlagForEveryAccount(
            flag: 'example-capability',
        )->execute();
        new DeactivateFlag(
            account: $pam->account,
            flag: 'example-capability',
        )->execute();

        Feature::flushCache();

        $this->assertFalse($pam->account->features()->active('example-capability'));
        $this->assertTrue($jim->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_refuses_a_flag_nobody_declared(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ActivateFlagForEveryAccount(
            flag: 'dundie-awards',
        )->execute();
    }
}
