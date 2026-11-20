<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AccessKindEnum;
use App\Enums\OfferKindEnum;
use App\Enums\VaultColourEnum;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\PossessionTag;
use App\Models\StripeOrder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_metered_on_the_cloud_instance_and_never_when_self_hosted(): void
    {
        $cloud = Account::factory()->create(['storage_limit_in_bytes' => 4096]);
        $withoutCeiling = Account::factory()->create(['storage_limit_in_bytes' => null]);

        $this->assertTrue($cloud->isMetered());
        $this->assertFalse($withoutCeiling->isMetered());

        config(['monica.self_hosted' => true]);

        $this->assertFalse($cloud->isMetered());
    }

    #[Test]
    public function it_reads_back_the_name_of_the_person_on_it(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->assertEquals('Michael Scott', $michael->account->ownerName());
    }

    #[Test]
    public function it_still_reads_back_a_name_for_somebody_who_was_deleted(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $account = $michael->account;
        $michael->delete();

        $this->assertEquals('Michael Scott', $account->ownerName());
    }

    #[Test]
    public function it_hands_back_a_deleted_person_when_asked_for_one(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $michael->delete();

        $this->assertNull($account->user);
        $this->assertTrue($michael->is($account->userIncludingDeleted));
    }

    #[Test]
    public function it_has_many_payments_newest_first(): void
    {
        $account = Account::factory()->create();

        $older = Payment::factory()->create(['account_id' => $account->id, 'billed_at' => now()->subMonth()]);
        $newer = Payment::factory()->create(['account_id' => $account->id, 'billed_at' => now()]);

        $this->assertTrue($account->payments()->exists());
        $this->assertSame([$newer->id, $older->id], $account->payments->pluck('id')->all());
    }

    #[Test]
    public function it_holds_no_full_access_on_the_free_offer(): void
    {
        $account = Account::factory()->create();

        $this->assertFalse($account->hasFullAccess());
        $this->assertNull($account->accessKind());
        $this->assertTrue($account->isOnFreeOffer());
    }

    #[Test]
    public function it_holds_full_access_once_an_order_is_settled(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        $this->assertTrue($account->hasFullAccess());
        $this->assertSame(AccessKindEnum::Lifetime, $account->accessKind());
        $this->assertFalse($account->isOnFreeOffer());
    }

    #[Test]
    public function it_holds_nothing_on_an_order_nobody_paid(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->create(['account_id' => $account->id]);

        $this->assertFalse($account->hasFullAccess());
        $this->assertNull($account->accessKind());
        $this->assertNull($account->settledOrder());
    }

    #[Test]
    public function it_holds_nothing_once_an_order_is_refunded(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->refunded()->create(['account_id' => $account->id]);

        $this->assertFalse($account->hasFullAccess());
        $this->assertTrue($account->orders()->exists());
    }

    #[Test]
    public function it_reads_access_an_administrator_granted_as_granted(): void
    {
        $account = Account::factory()->withGrantedAccess()->create();

        $this->assertTrue($account->hasFullAccess());
        $this->assertSame(AccessKindEnum::Granted, $account->accessKind());
        $this->assertNull($account->settledOrder());
    }

    #[Test]
    public function it_reads_a_settled_order_as_the_purchase_even_beside_a_grant(): void
    {
        $account = Account::factory()->withGrantedAccess()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        $this->assertSame(AccessKindEnum::Lifetime, $account->accessKind());
    }

    #[Test]
    public function it_holds_full_access_while_a_subscription_is_valid(): void
    {
        $account = Account::factory()->create();
        $this->subscribeAccount($account);

        $this->assertTrue($account->hasFullAccess());
        $this->assertSame(AccessKindEnum::Subscription, $account->accessKind());
        $this->assertFalse($account->isOnFreeOffer());
    }

    #[Test]
    public function it_goes_back_to_the_free_offer_once_a_subscription_is_over(): void
    {
        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly', 'canceled', now()->subDay());

        $this->assertFalse($account->hasFullAccess());
        $this->assertTrue($account->isOnFreeOffer());
    }

    #[Test]
    public function it_keeps_full_access_while_a_payment_is_being_retried(): void
    {
        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly', 'past_due');

        $this->assertTrue($account->hasFullAccess());
    }

    #[Test]
    public function it_narrows_a_query_to_each_kind_of_access_as_it_reads_one_account(): void
    {
        Account::factory()->create();
        StripeOrder::factory()->create(['account_id' => Account::factory()->create()->id]);
        StripeOrder::factory()->settled()->create(['account_id' => Account::factory()->withGrantedAccess()->create()->id]);
        Account::factory()->withGrantedAccess()->create();
        $this->subscribeAccount(Account::factory()->create(), stripeId: 'sub_jim');
        $this->subscribeAccount(Account::factory()->create(), status: 'past_due', stripeId: 'sub_pam');
        $this->subscribeAccount(Account::factory()->create(), status: 'canceled', endsAt: now()->subDay(), stripeId: 'sub_dwight');

        foreach ([null, ...AccessKindEnum::cases()] as $kind) {
            $expected = Account::query()->get()->filter(fn (Account $account): bool => $account->accessKind() === $kind)->pluck('id')->sort()->values()->all();

            $this->assertSame($expected, Account::query()->whereAccessKind($kind)->orderBy('id')->pluck('id')->all(), $kind->value ?? 'free');
        }
    }

    #[Test]
    public function it_is_never_on_the_free_offer_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $account = Account::factory()->create();

        $this->assertFalse($account->isOnFreeOffer());
        $this->assertNull($account->vaultLimit());
        $this->assertNull($account->contactLimit());
        $this->assertNull($account->storageLimitInBytes());
        $this->assertSame([], $account->availableOffers());
    }

    #[Test]
    public function it_caps_the_vaults_and_the_contacts_of_a_free_account_only(): void
    {
        config(['monica.free_vault_limit' => 1, 'monica.free_contact_limit' => 30]);

        $free = Account::factory()->create();
        $paid = Account::factory()->withLifetimeAccess()->create();

        $this->assertSame(1, $free->vaultLimit());
        $this->assertSame(30, $free->contactLimit());
        $this->assertNull($paid->vaultLimit());
        $this->assertNull($paid->contactLimit());
    }

    #[Test]
    public function it_lifts_the_storage_ceiling_while_full_access_is_held_without_rewriting_the_stamped_one(): void
    {
        config(['monica.paid_storage_limit_in_bytes' => 10737418240]);

        $account = Account::factory()->create(['storage_limit_in_bytes' => 104857600]);

        $this->assertSame(104857600, $account->storageLimitInBytes());

        $account->update(['access_granted_at' => now()]);

        $this->assertSame(10737418240, $account->storageLimitInBytes());
        $this->assertSame(104857600, $account->storage_limit_in_bytes);

        $account->update(['access_granted_at' => null]);

        $this->assertSame(104857600, $account->storageLimitInBytes());
    }

    #[Test]
    public function it_keeps_a_ceiling_raised_above_the_paid_figure(): void
    {
        config(['monica.paid_storage_limit_in_bytes' => 10737418240]);

        $account = Account::factory()->withLifetimeAccess()->create(['storage_limit_in_bytes' => 21474836480]);

        $this->assertSame(21474836480, $account->storageLimitInBytes());
    }

    #[Test]
    public function it_counts_the_contacts_the_cap_is_measured_against(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);

        Contact::factory()->count(2)->create(['account_id' => $account->id, 'vault_id' => $vault->id]);
        Contact::factory()->create(['account_id' => $account->id, 'vault_id' => $vault->id, 'is_me' => true]);
        Contact::factory()->create(['account_id' => $account->id, 'vault_id' => $vault->id, 'is_partial' => true]);

        $this->assertSame(2, $account->contactCount());
    }

    #[Test]
    public function it_offers_nothing_further_to_a_lifetime_holder(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->withLifetimeAccess()->create();

        $this->assertSame([], $account->availableOffers());
        $this->assertTrue($account->offersAvailable());
    }

    #[Test]
    public function it_offers_every_kind_to_a_free_account(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();

        $this->assertSame(
            ['price_monthly', 'price_yearly', 'price_lifetime'],
            array_column($account->availableOffers(), 'price_id'),
        );
    }

    #[Test]
    public function it_offers_a_subscriber_only_what_sits_above_their_offer(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly');

        $this->assertSame(
            ['price_yearly', 'price_lifetime'],
            array_column($account->availableOffers(), 'price_id'),
        );
        $this->assertSame(OfferKindEnum::Monthly, $account->subscriptionOfferKind());
    }

    #[Test]
    public function it_offers_a_subscriber_already_moving_to_yearly_lifetime_access_alone(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly')->forceFill(['scheduled_stripe_price' => 'price_yearly'])->save();

        $this->assertSame(['price_lifetime'], array_column($account->availableOffers(), 'price_id'));
        $this->assertSame(OfferKindEnum::Yearly, $account->scheduledOfferKind());
        $this->assertSame(OfferKindEnum::Monthly, $account->subscriptionOfferKind());
    }

    #[Test]
    public function it_names_no_waiting_move_on_a_subscription_that_is_ending(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly', 'active', now()->addWeek())
            ->forceFill(['scheduled_stripe_price' => 'price_yearly'])
            ->save();

        $this->assertNull($account->scheduledOfferKind());
    }

    #[Test]
    public function it_offers_a_yearly_subscriber_lifetime_access_alone(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_yearly');

        $this->assertSame(['price_lifetime'], array_column($account->availableOffers(), 'price_id'));
    }

    #[Test]
    public function it_says_the_offers_could_not_be_read_when_stripe_does_not_answer(): void
    {
        $client = $this->fakeStripe();
        $client->failOn('prices*');

        $account = Account::factory()->create();

        $this->assertSame([], $account->availableOffers());
        $this->assertFalse($account->offersAvailable());
    }

    #[Test]
    public function it_reads_when_a_subscription_next_bills_off_stripe(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $this->subscribeAccount($account);

        $this->assertSame($this->stripePeriodEnd()->format('Y-m-d'), $account->nextBillingDate()?->format('Y-m-d'));
    }

    #[Test]
    public function it_has_no_billing_date_without_a_running_subscription(): void
    {
        $this->fakeStripe();

        $free = Account::factory()->create();
        $this->assertNull($free->nextBillingDate());

        $ending = Account::factory()->create();
        $this->subscribeAccount($ending, 'price_monthly', 'active', now()->addWeek());
        $this->assertNull($ending->nextBillingDate());
    }

    #[Test]
    public function it_costs_a_date_rather_than_a_screen_when_stripe_does_not_answer(): void
    {
        $client = $this->fakeStripe();
        $client->failOn('subscription_items*');

        $account = Account::factory()->create();
        $this->subscribeAccount($account);

        $this->assertNull($account->nextBillingDate());
    }

    #[Test]
    public function it_names_the_person_on_it_to_stripe(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott', 'email' => 'michael.scott@dundermifflin.com']);
        $account = $michael->account;

        $this->assertSame('Michael Scott', $account->stripeName());
        $this->assertSame('michael.scott@dundermifflin.com', $account->stripeEmail());
    }

    #[Test]
    public function it_names_nobody_to_stripe_when_there_is_nobody_on_it(): void
    {
        $account = Account::factory()->create();

        $this->assertNull($account->stripeName());
        $this->assertNull($account->stripeEmail());
    }

    #[Test]
    public function it_reports_the_preference_the_person_on_it_chose(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id, 'uses_readable_addresses' => false]);

        $this->assertFalse($account->usesReadableAddresses());

        Account::forgetAddressPreferences();
        $account->user->update(['uses_readable_addresses' => true]);

        $this->assertTrue($account->usesReadableAddresses());
    }

    #[Test]
    public function it_still_answers_for_an_account_whose_person_was_deleted(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id, 'uses_readable_addresses' => false]);
        $user->delete();

        Account::forgetAddressPreferences();

        $this->assertFalse($account->usesReadableAddresses());
    }

    #[Test]
    public function an_account_with_nobody_on_it_builds_readable_addresses(): void
    {
        $account = Account::factory()->create();

        $this->assertTrue($account->usesReadableAddresses());
    }

    #[Test]
    public function it_asks_the_database_once_per_account_in_a_request(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);

        Account::forgetAddressPreferences();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $account->usesReadableAddresses();
        $account->usesReadableAddresses();
        $account->usesReadableAddresses();

        $this->assertSame(1, $queries);
    }

    #[Test]
    public function it_gives_the_next_possession_tag_the_colour_after_the_latest_one(): void
    {
        $account = Account::factory()->create();

        $this->assertSame(VaultColourEnum::Blue, $account->nextPossessionTagColour());

        PossessionTag::factory()->create(['account_id' => $account->id, 'colour' => VaultColourEnum::Slate->value]);
        PossessionTag::factory()->create(['account_id' => $account->id, 'colour' => VaultColourEnum::Green->value]);

        $this->assertSame(VaultColourEnum::Amber, $account->nextPossessionTagColour());

        PossessionTag::factory()->create(['account_id' => $account->id, 'colour' => VaultColourEnum::Slate->value]);

        $this->assertSame(VaultColourEnum::Blue, $account->nextPossessionTagColour());
    }
}
