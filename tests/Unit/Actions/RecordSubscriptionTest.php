<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RecordSubscription;
use App\Enums\UserActionEnum;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_writes_nothing_about_access_on_the_account(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $account = $michael->account;
        $subscription = $this->subscribeAccount($account);

        $result = new RecordSubscription(account: $account, subscription: $subscription)->execute();

        $this->assertInstanceOf(Account::class, $result);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'billing_email' => 'michael.scott@dundermifflin.com',
        ]);
        $this->assertTrue($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_tells_the_owner_once_that_access_is_open(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);

        new RecordSubscription(account: $michael->account, subscription: $subscription)->execute();

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    public function it_writes_the_subscription_into_the_account_journal(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);

        new RecordSubscription(account: $michael->account, subscription: $subscription)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SubscriptionStarted
            && $job->isAbout($subscription));
    }

    #[Test]
    public function it_tells_nobody_when_there_is_nobody_on_the_account(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create();
        $subscription = $this->subscribeAccount($account);

        new RecordSubscription(account: $account, subscription: $subscription)->execute();

        Notification::assertNothingSent();
    }
}
