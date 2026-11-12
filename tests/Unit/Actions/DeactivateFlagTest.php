<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

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

class DeactivateFlagTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_deactivates_the_flag_for_one_account_and_writes_it_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $pam = User::factory()->create();
        $jim = User::factory()->create();
        $pam->account->features()->activate('example-capability');
        $jim->account->features()->activate('example-capability');

        new DeactivateFlag(
            account: $pam->account,
            flag: 'example-capability',
            administrator: $michael,
        )->execute();

        Feature::flushCache();

        $this->assertFalse($pam->account->features()->active('example-capability'));
        $this->assertTrue($jim->account->features()->active('example-capability'));
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::FlagDeactivated
            && $job->administrator?->is($michael) === true
            && $job->account?->is($pam->account) === true
            && $job->parameters === ['flag' => 'example-capability', 'name' => $pam->name]);
    }

    #[Test]
    public function it_refuses_a_flag_nobody_declared(): void
    {
        $pam = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DeactivateFlag(
            account: $pam->account,
            flag: 'dundie-awards',
        )->execute();
    }
}
