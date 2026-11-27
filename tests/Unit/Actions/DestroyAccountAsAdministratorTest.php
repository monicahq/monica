<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyAccountAsAdministrator;
use App\Enums\AdministratorActionEnum;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Exceptions\ProviderUnreachable;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyAccountAsAdministratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_account_and_records_only_its_number(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $farm = Vault::factory()->create(['account_id' => $dwight->account_id]);
        $accountId = $dwight->account_id;

        new DestroyAccountAsAdministrator(account: $dwight->account, administrator: $michael)->execute();

        $this->assertModelMissing($dwight);
        $this->assertModelMissing($farm);
        $this->assertDatabaseMissing('accounts', ['id' => $accountId]);

        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::AccountDestroyed
            && $job->administrator->is($michael)
            && $job->account === null
            && $job->parameters === ['account' => $accountId]);
    }

    #[Test]
    public function it_refuses_to_let_an_administrator_delete_their_own_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();

        $refused = false;

        try {
            new DestroyAccountAsAdministrator(account: $michael->account, administrator: $michael)->execute();
        } catch (AdministratorCannotLockThemselvesOut) {
            $refused = true;
        }

        $this->assertTrue($refused);
        $this->assertModelExists($michael->account);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_deletes_nothing_and_records_nothing_when_stripe_will_not_stop_the_subscription(): void
    {
        Queue::fake();

        $client = $this->fakeStripe();
        $client->failOn('subscriptions*');

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $this->subscribeAccount($dwight->account);

        $refused = false;

        try {
            new DestroyAccountAsAdministrator(account: $dwight->account, administrator: $michael)->execute();
        } catch (ProviderUnreachable) {
            $refused = true;
        }

        $this->assertTrue($refused);
        $this->assertModelExists($dwight->account);
        Queue::assertNothingPushed();
    }
}
