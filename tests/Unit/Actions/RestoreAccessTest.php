<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreAccess;
use App\Enums\UserActionEnum;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
        Queue::fake();
        Notification::fake();
    }

    #[Test]
    public function it_puts_the_same_order_back_when_the_money_comes_back_to_us(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id]);
        $payment = Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_subscription_id' => null,
        ]);

        $result = new RestoreAccess(account: $michael->account, payment: $payment)->execute();

        $this->assertInstanceOf(Account::class, $result);
        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
        $this->assertSame(1, StripeOrder::query()->count());
        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::FullAccessRestored
            && $job->isAbout($payment));
    }

    #[Test]
    public function it_puts_back_an_order_a_dispute_had_taken_away(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->disputed()->create(['account_id' => $michael->account_id]);

        new RestoreAccess(account: $michael->account)->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
    }

    #[Test]
    public function it_works_out_the_purchase_from_the_account_when_nothing_names_the_payment(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id]);

        new RestoreAccess(account: $michael->account)->execute();

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_cannot_bring_a_cancelled_subscription_back(): void
    {
        $michael = User::factory()->create();
        $payment = Payment::factory()->forSubscription()->create(['account_id' => $michael->account_id]);

        new RestoreAccess(account: $michael->account, payment: $payment)->execute();

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_for_an_account_that_never_bought_anything(): void
    {
        $michael = User::factory()->create();

        new RestoreAccess(account: $michael->account)->execute();

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_leaves_one_record_and_one_email_when_stripe_repeats_itself(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id]);
        $payment = Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_subscription_id' => null,
        ]);

        new RestoreAccess(account: $michael->account, payment: $payment)->execute();
        new RestoreAccess(account: $michael->account->fresh(), payment: $payment)->execute();

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_says_nothing_to_somebody_who_never_lost_access(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);
        $payment = Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_subscription_id' => null,
        ]);

        new RestoreAccess(account: $michael->account->fresh(), payment: $payment)->execute();

        Notification::assertNothingSent();
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_tells_nobody_when_there_is_nobody_on_the_account(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->refunded()->create(['account_id' => $account->id]);
        $payment = Payment::factory()->create(['account_id' => $account->id, 'stripe_subscription_id' => null]);

        new RestoreAccess(account: $account, payment: $payment)->execute();

        $this->assertTrue($account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }
}
