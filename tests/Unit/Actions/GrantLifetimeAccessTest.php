<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GrantLifetimeAccess;
use App\Enums\AccessKindEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GrantLifetimeAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_the_full_product(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create();

        new GrantLifetimeAccess(account: $account, administrator: $michael)->execute();

        $account->refresh();

        $this->assertTrue($account->hasFullAccess());
        $this->assertNotNull($account->access_granted_at);
        $this->assertSame(AccessKindEnum::Granted, $account->accessKind());
        $this->assertSame(0, StripeOrder::query()->count());

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_leaves_a_grant_that_is_already_there_alone(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $granted = now()->subYears(2)->startOfDay();
        $account = Account::factory()->create(['access_granted_at' => $granted]);

        new GrantLifetimeAccess(account: $account, administrator: $michael)->execute();

        $this->assertEquals($granted->toDateString(), $account->refresh()->access_granted_at->toDateString());
    }
}
