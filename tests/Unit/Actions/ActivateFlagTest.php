<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateFlag;
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

class ActivateFlagTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_activates_the_flag_for_one_account_and_writes_it_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $pam = User::factory()->create();
        $jim = User::factory()->create();

        new ActivateFlag(
            account: $pam->account,
            flag: 'example-capability',
            administrator: $michael,
        )->execute();

        Feature::flushCache();

        $this->assertTrue($pam->account->features()->active('example-capability'));
        $this->assertFalse($jim->account->features()->active('example-capability'));
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::FlagActivated
            && $job->administrator?->is($michael) === true
            && $job->account?->is($pam->account) === true
            && $job->parameters === ['flag' => 'example-capability', 'name' => $pam->name]);
    }

    #[Test]
    public function it_gives_the_same_answer_whoever_is_signed_in(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $jim = User::factory()->create();

        new ActivateFlag(
            account: $pam->account,
            flag: 'example-capability',
        )->execute();

        Feature::flushCache();
        $this->assertTrue($pam->account->features()->active('example-capability'));

        $this->actingAs($jim);
        Feature::flushCache();
        $this->assertTrue($pam->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_writes_it_down_as_the_console_when_no_administrator_is_named(): void
    {
        Queue::fake();

        $pam = User::factory()->create();

        new ActivateFlag(
            account: $pam->account,
            flag: 'example-capability',
        )->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->administrator === null);
    }

    #[Test]
    public function it_refuses_a_flag_nobody_declared(): void
    {
        $pam = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ActivateFlag(
            account: $pam->account,
            flag: 'dundie-awards',
        )->execute();
    }
}
