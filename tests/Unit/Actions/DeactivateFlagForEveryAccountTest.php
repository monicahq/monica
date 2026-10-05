<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateFlagForEveryAccount;
use App\Actions\DeactivateFlagForEveryAccount;
use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateFlagForEveryAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'));
    }

    #[Test]
    public function it_deactivates_the_flag_for_every_account_and_writes_it_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $pam = User::factory()->create();
        $pam->account->features()->activate('example-capability');

        new DeactivateFlagForEveryAccount(
            flag: 'example-capability',
            administrator: $michael,
        )->execute();

        Feature::flushCache();

        $this->assertFalse($pam->account->features()->active('example-capability'));
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::FlagDeactivatedForEveryAccount
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
        new DeactivateFlagForEveryAccount(
            flag: 'example-capability',
        )->execute();

        $ryan = User::factory()->create();

        $this->assertFalse($ryan->account->features()->active('example-capability'));
    }

    #[Test]
    public function it_keeps_what_was_created_while_the_flag_was_on(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $pam->account_id])->id,
        ]);

        new ActivateFlagForEveryAccount(
            flag: 'example-capability',
        )->execute();
        new DeactivateFlagForEveryAccount(
            flag: 'example-capability',
        )->execute();
        new ActivateFlagForEveryAccount(
            flag: 'example-capability',
        )->execute();

        $this->assertModelExists($contact);
    }

    #[Test]
    public function it_refuses_a_flag_nobody_declared(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DeactivateFlagForEveryAccount(
            flag: 'dundie-awards',
        )->execute();
    }
}
